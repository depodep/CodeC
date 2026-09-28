import ctypes
from ctypes import wintypes
import urllib.request
import json
import time

winscard = ctypes.windll.WinSCard

SCARD_SCOPE_USER = 0
SCARD_SHARE_SHARED = 2
SCARD_PROTOCOL_T0 = 1
SCARD_PROTOCOL_T1 = 2
SCARD_PROTOCOL_Tx = SCARD_PROTOCOL_T0 | SCARD_PROTOCOL_T1
SCARD_LEAVE_CARD = 0

class SCARD_IO_REQUEST(ctypes.Structure):
    _fields_ = [
        ("dwProtocol", wintypes.DWORD),
        ("cbPciLength", wintypes.DWORD)
    ]

try:
    g_rgSCardT0Pci = SCARD_IO_REQUEST.in_dll(winscard, "g_rgSCardT0Pci")
    g_rgSCardT1Pci = SCARD_IO_REQUEST.in_dll(winscard, "g_rgSCardT1Pci")
except Exception:
    g_rgSCardT0Pci = SCARD_IO_REQUEST(1, 8)
    g_rgSCardT1Pci = SCARD_IO_REQUEST(2, 8)

# Unified Attendance & Registration Endpoint
API_URL = "http://127.0.0.1:8000/api/nfc/tap"
HEARTBEAT_URL = API_URL.replace("/api/nfc/tap", "/api/nfc/heartbeat")

def acr122u_reader_present():
    hContext = wintypes.HANDLE()
    ret = winscard.SCardEstablishContext(SCARD_SCOPE_USER, None, None, ctypes.byref(hContext))
    if ret != 0:
        return False

    try:
        reader_len = wintypes.DWORD(0)
        ret = winscard.SCardListReadersA(hContext, None, None, ctypes.byref(reader_len))
        if ret != 0 or reader_len.value <= 1:
            return False

        buf = (ctypes.c_char * reader_len.value)()
        ret = winscard.SCardListReadersA(hContext, None, buf, ctypes.byref(reader_len))
        if ret != 0:
            return False

        readers = bytes(buf).decode('latin1').split('\x00')
        return any(reader.strip() for reader in readers)
    finally:
        winscard.SCardReleaseContext(hContext)

def read_acr122u_uid():
    hContext = wintypes.HANDLE()
    ret = winscard.SCardEstablishContext(SCARD_SCOPE_USER, None, None, ctypes.byref(hContext))
    if ret != 0:
        return None

    try:
        reader_len = wintypes.DWORD(0)
        ret = winscard.SCardListReadersA(hContext, None, None, ctypes.byref(reader_len))
        if ret != 0 or reader_len.value <= 1:
            return None

        buf = (ctypes.c_char * reader_len.value)()
        ret = winscard.SCardListReadersA(hContext, None, buf, ctypes.byref(reader_len))
        if ret != 0:
            return None

        readers = bytes(buf).decode('latin1').split('\x00')
        target_reader = None
        for r in readers:
            if r.strip():
                target_reader = r
                break

        if not target_reader:
            return None

        hCard = wintypes.HANDLE()
        active_proto = wintypes.DWORD()
        ret = winscard.SCardConnectA(
            hContext,
            target_reader.encode('latin1'),
            SCARD_SHARE_SHARED,
            SCARD_PROTOCOL_Tx,
            ctypes.byref(hCard),
            ctypes.byref(active_proto)
        )
        if ret != 0:
            return None

        try:
            pci = g_rgSCardT1Pci if active_proto.value == SCARD_PROTOCOL_T1 else g_rgSCardT0Pci
            send_apdu = (ctypes.c_ubyte * 5)(0xFF, 0xCA, 0x00, 0x00, 0x00)
            recv_buf = (ctypes.c_ubyte * 258)()
            recv_len = wintypes.DWORD(258)

            ret = winscard.SCardTransmit(
                hCard,
                ctypes.byref(pci),
                send_apdu,
                len(send_apdu),
                None,
                recv_buf,
                ctypes.byref(recv_len)
            )

            if ret == 0 and recv_len.value >= 2:
                sw1 = recv_buf[recv_len.value - 2]
                sw2 = recv_buf[recv_len.value - 1]
                if sw1 == 0x90 and sw2 == 0x00:
                    uid_bytes = bytes(recv_buf[:recv_len.value - 2])
                    return uid_bytes.hex().upper()
        finally:
            winscard.SCardDisconnect(hCard, SCARD_LEAVE_CARD)
    finally:
        winscard.SCardReleaseContext(hContext)

    return None

def send_to_laravel(uid):
    try:
        url = f"{API_URL}?tag_id={uid}"
        req = urllib.request.Request(url, headers={'User-Agent': 'SIATRACK-Bridge', 'Accept': 'application/json'})
        with urllib.request.urlopen(req, timeout=3) as response:
            res_body = response.read().decode('utf-8')
            return json.loads(res_body)
    except urllib.error.HTTPError as e:
        try:
            return json.loads(e.read().decode('utf-8'))
        except Exception:
            return {"success": False, "message": f"HTTP Error {e.code}"}
    except Exception as e:
        return {"success": False, "message": str(e)}

def send_heartbeat():
    try:
        reader_connected = "1" if acr122u_reader_present() else "0"
        req = urllib.request.Request(
            f"{HEARTBEAT_URL}?reader_connected={reader_connected}",
            headers={
                "User-Agent": "SIATRACK-Bridge",
                "Accept": "application/json",
            },
        )
        with urllib.request.urlopen(req, timeout=3):
            return True
    except Exception:
        return False

print("==================================================")
print("       SIATRACK ACR122U Smart Bridge Online       ")
print("==================================================")
print("[READY] Place an NFC card on the ACR122U reader...\n")

last_uid = None
last_heartbeat = 0
heartbeat_interval = 2

while True:
    try:
        now = time.time()
        if now - last_heartbeat >= heartbeat_interval:
            send_heartbeat()
            last_heartbeat = now

        uid = read_acr122u_uid()
        if uid and uid != last_uid:
            last_uid = uid
            print(f"\n[CARD TAP DETECTED] UID: {uid}")
            
            result = send_to_laravel(uid)
            
            if result.get('success'):
                action = result.get('action', 'LOGGED')
                status = result.get('status', 'ON-TIME')
                student = result.get('student', {}).get('name', 'Student')
                time_str = result.get('time', '')
                print(f"[ATTENDANCE SUCCESS] {action}: {student} | Status: {status} | Time: {time_str}")
            elif result.get('is_registered') is False:
                print(f"[UNREGISTERED CARD] UID: {uid} -> Ready for student assignment in Admin Panel")
            else:
                print(f"[SERVER RESPONSE] {result.get('message', 'No details')}")
                
            time.sleep(1.2)
        elif not uid:
            last_uid = None
    except Exception as e:
        pass
    time.sleep(0.2)
