<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\HtmlString;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->maxContentWidth('full')
            ->login()
            ->brandName('Helena Beach Resort')
            ->brandLogo(new HtmlString('
                <div style="display: flex; align-items: center; gap: 0.625rem; height: 2.25rem;">
                    <img src="'.url('images/logo.jpg').'" alt="Helena Beach" style="height: 1.75rem; width: auto; border-radius: 0.375rem; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span style="font-family: Playfair Display, Georgia, serif; font-size: 1rem; font-weight: 700; white-space: nowrap; color: #fff;">Helena Beach</span>
                </div>
            '))
            ->brandLogoHeight('2.25rem')
            ->colors([
                'primary' => '#0d9488',
            ])
            ->font('Inter')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->renderHook(
                'panels::styles.after',
                fn (): string => '<link rel="preconnect" href="https://fonts.bunny.net">
                    <link href="https://fonts.bunny.net/css?family=playfair-display:400,600,700&display=swap" rel="stylesheet" />'
            )
            ->renderHook(
                'panels::body.start',
                fn () => new HtmlString('
                    <style>
                        :root { --sidebar-width: 16rem; }
                        .fi-sidebar-nav { padding-top: 0.5rem; }
                        .fi-sidebar-item-active a { border-left: 3px solid #0d9488; background: linear-gradient(to right, rgba(13,148,136,0.06), transparent); }
                        .fi-logo { filter: drop-shadow(0 1px 2px rgba(0,0,0,0.15)); }
                        .fi-topbar { background: rgba(255,255,255,0.9) !important; backdrop-filter: blur(12px) !important; }
                        .fi-simple-layout { background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 50%, #fef3c7 100%) !important; min-height: 100vh; display: flex !important; align-items: center !important; justify-content: center !important; }
                        .fi-simple-page { background: white !important; border-radius: 1rem !important; box-shadow: 0 20px 60px rgba(13,148,136,0.15) !important; padding: 2rem !important; max-width: 28rem !important; width: 100% !important; margin: 2rem auto !important; }
                        .fi-simple-page .fi-btn { border-radius: 0.75rem !important; padding: 0.75rem 1.5rem !important; font-weight: 600 !important; transition: all 0.2s !important; }
                        .fi-simple-page .fi-btn:hover { transform: translateY(-1px) !important; box-shadow: 0 8px 25px rgba(13,148,136,0.25) !important; }
                        .fi-simple-page .fi-input { border-radius: 0.75rem !important; border-color: #e5e7eb !important; transition: all 0.2s !important; }
                        .fi-simple-page .fi-input:focus { border-color: #0d9488 !important; box-shadow: 0 0 0 3px rgba(13,148,136,0.1) !important; }
                        .fi-simple-header { text-align: center !important; }
                        .fi-simple-header-heading { font-size: 1.25rem !important; font-weight: 700 !important; color: #111827 !important; margin-top: 0.5rem !important; }
                        .fi-simple-main { width: 100% !important; max-width: none !important; padding: 0 !important; }
                        @media (max-width: 640px) {
                            .fi-simple-page { margin: 1rem !important; padding: 1.5rem !important; }
                        }
                    </style>
                ')
            )
            ->renderHook(
                'panels::simple-layout.start',
                fn () => new HtmlString('
                    <div style="position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 0;">
                        <div style="position: absolute; top: -10%; right: -5%; width: 40%; height: 60%; background: radial-gradient(ellipse, rgba(13,148,136,0.08), transparent 70%); border-radius: 50%;"></div>
                        <div style="position: absolute; bottom: -10%; left: -5%; width: 50%; height: 50%; background: radial-gradient(ellipse, rgba(245,158,11,0.06), transparent 70%); border-radius: 50%;"></div>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80%; height: 80%; background: radial-gradient(ellipse, rgba(13,148,136,0.03), transparent 70%); border-radius: 50%;"></div>
                    </div>
                ')
            )
            ->renderHook(
                'panels::sidebar.nav.start',
                fn () => new HtmlString('
                    <div style="padding: 0.75rem 1rem; margin: 0 0.5rem 0.5rem; border-radius: 0.5rem; background: linear-gradient(135deg, rgba(13,148,136,0.08), rgba(13,148,136,0.02)); border: 1px solid rgba(13,148,136,0.1);">
                        <p style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #0d9488; margin: 0 0 0.25rem;">Management</p>
                        <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">Helena Beach Resort Admin</p>
                    </div>
                ')
            )
            ->renderHook(
                'panels::footer',
                fn () => new HtmlString('
                    <div style="text-align: center; padding: 1rem; font-size: 0.75rem; color: #9ca3af; border-top: 1px solid #f3f4f6; margin-top: 1rem;">
                        <a href="'.route('home').'" style="color: #0d9488; text-decoration: none; font-weight: 500;">← Back to Website</a>
                        &nbsp;&middot;&nbsp;
                        &copy; '.date('Y').' Helena Beach Resort
                    </div>
                ')
            )
            ->navigationGroups([
                'Content',
                'Bookings',
                'Settings',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
