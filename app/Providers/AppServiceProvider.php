<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Gate;
use App\Models\OutboundMessage;
use App\Policies\OutboundMessagePolicy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(OutboundMessage::class, OutboundMessagePolicy::class);

        View::composer('*', function ($view) {
            $year = '2027-2028';
            $sem  = '2nd Semester';
            try {
                if (Schema::hasTable('academic_periods')) {
                    $active = DB::table('academic_periods')->where('is_active', 1)->latest('updated_at')->first();
                    if ($active) {
                        $raw = $active->school_year ?? $active->academic_year ?? $active->name ?? $year;
                        // Kunin lamang ang dalawang valid na 4-digit years (YYYY-YYYY)
                        if (preg_match('/(20\d{2})[^\d<]+(20\d{2})/', $raw, $m)) {
                            $year = $m[1] . '-' . $m[2];
                        } else {
                            $year = trim(str_replace(['A.Y.', 'A.Y', 'AY'], '', $raw));
                        }
                        $sem = $active->semester ?? $sem;
                    }
                }
            } catch (\Throwable $e) {}

            $view->with('activeSchoolYear', $year)->with('activeSemester', $sem);
        });
    }
}