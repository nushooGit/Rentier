<?php

namespace App\Providers;

use App\Services\Utilities\PdfOcrTextExtractor;
use App\Services\Utilities\PdfTextExtractor;
use App\Services\Utilities\PopplerPdfTextExtractor;
use App\Services\Utilities\TesseractPdfOcrTextExtractor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PdfTextExtractor::class, PopplerPdfTextExtractor::class);
        $this->app->bind(PdfOcrTextExtractor::class, TesseractPdfOcrTextExtractor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(10)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
