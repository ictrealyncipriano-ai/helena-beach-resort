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
use Filament\View\PanelsRenderHook;
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
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; height: auto;">
                    <img src="'.url('images/logo.jpg').'" alt="Helena Beach" style="height: 2.5rem; width: auto; border-radius: 0.5rem; flex-shrink: 0; box-shadow: 0 2px 8px rgba(13,148,136,0.2);">
                    <span style="font-family: Playfair Display, Georgia, serif; font-size: 1.25rem; font-weight: 700; white-space: nowrap; color: #0d9488;">Helena Beach</span>
                </div>
            '))
            ->brandLogoHeight('3rem')
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
                'panels::auth.login.form.before',
                fn () => new HtmlString('
                    <p style="text-align: center; color: #6b7280; font-size: 0.875rem; line-height: 1.5; margin-bottom: 1.5rem;">
                        Welcome back! Sign in to manage your resort.
                    </p>
                ')
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

                        .fi-simple-layout {
                            background: linear-gradient(135deg, #f0fdfa 0%, #d1fae5 40%, #fef3c7 100%) !important;
                            min-height: 100vh;
                            display: flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                            padding: 1.5rem !important;
                        }

                        .fi-simple-page {
                            background: rgba(255,255,255,0.97) !important;
                            border-radius: 1.25rem !important;
                            box-shadow:
                                0 1px 3px rgba(0,0,0,0.04),
                                0 8px 32px rgba(13,148,136,0.12),
                                0 24px 60px rgba(13,148,136,0.08) !important;
                            padding: 2.5rem !important;
                            max-width: 26rem !important;
                            width: 100% !important;
                            margin: 0 auto !important;
                            border: 1px solid rgba(13,148,136,0.08) !important;
                            position: relative !important;
                            animation: loginFadeIn 0.5s ease-out;
                        }

                        .fi-simple-page::before {
                            content: "";
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            height: 4px;
                            background: linear-gradient(90deg, #0d9488, #14b8a6, #5eead4);
                            border-radius: 1.25rem 1.25rem 0 0;
                        }

                        @keyframes loginFadeIn {
                            from { opacity: 0; transform: translateY(12px); }
                            to { opacity: 1; transform: translateY(0); }
                        }

                        .fi-simple-main { width: 100% !important; max-width: none !important; padding: 0 !important; background: transparent !important; box-shadow: none !important; --tw-ring-color: transparent !important; }

                        .fi-simple-header { text-align: center !important; padding-top: 0.5rem !important; }
                        .fi-simple-header-heading {
                            font-size: 1.125rem !important;
                            font-weight: 600 !important;
                            color: #374151 !important;
                            margin-top: 1rem !important;
                            margin-bottom: 0 !important;
                        }

                        .fi-fo-field { margin-bottom: 1.25rem !important; }
                        .fi-fo-field-label { font-size: 0.813rem !important; font-weight: 500 !important; color: #374151 !important; margin-bottom: 0.375rem !important; display: block !important; }

                        .fi-input-wrp {
                            border-radius: 0.75rem !important;
                            border: 1.5px solid #e5e7eb !important;
                            background: #f9fafb !important;
                            transition: all 0.2s ease !important;
                            position: relative !important;
                            box-shadow: none !important;
                        }
                        .fi-input-wrp:focus-within {
                            border-color: #0d9488 !important;
                            background: white !important;
                            box-shadow: 0 0 0 4px rgba(13,148,136,0.08) !important;
                        }

                        .fi-input-wrp-content-ctn { position: relative !important; }

                        .fi-simple-page .fi-input {
                            border-radius: 0.75rem !important;
                            border: none !important;
                            background: transparent !important;
                            padding: 0.75rem 1rem 0.75rem 2.75rem !important;
                            font-size: 0.875rem !important;
                            color: #111827 !important;
                            box-shadow: none !important;
                            transition: none !important;
                        }
                        .fi-simple-page .fi-input:focus {
                            border: none !important;
                            box-shadow: none !important;
                        }
                        .fi-simple-page .fi-input::placeholder { color: #9ca3af !important; }

                        .fi-input-wrp-suffix { padding-right: 0.5rem !important; }

                        .fi-fo-field:has(input[type="checkbox"]) {
                            display: flex !important;
                            align-items: center !important;
                            margin-bottom: 1.5rem !important;
                        }
                        .fi-fo-field:has(input[type="checkbox"]) .fi-fo-field-label {
                            margin-bottom: 0 !important;
                            display: inline-flex !important;
                            align-items: center !important;
                            gap: 0.5rem !important;
                            cursor: pointer !important;
                            font-size: 0.813rem !important;
                            color: #6b7280 !important;
                        }
                        .fi-checkbox-input {
                            border-radius: 0.375rem !important;
                            border: 1.5px solid #d1d5db !important;
                            width: 1rem !important;
                            height: 1rem !important;
                            cursor: pointer !important;
                            accent-color: #0d9488 !important;
                        }

                        .fi-simple-page .fi-ac-btn-action.fi-btn {
                            width: 100% !important;
                            border-radius: 0.75rem !important;
                            padding: 0.75rem 1.5rem !important;
                            font-weight: 600 !important;
                            font-size: 0.875rem !important;
                            background: linear-gradient(135deg, #0d9488, #14b8a6) !important;
                            border: none !important;
                            color: white !important;
                            transition: all 0.2s ease !important;
                            box-shadow: 0 4px 14px rgba(13,148,136,0.3) !important;
                            letter-spacing: 0.01em !important;
                        }
                        .fi-simple-page .fi-ac-btn-action.fi-btn:hover {
                            transform: translateY(-1px) !important;
                            box-shadow: 0 8px 25px rgba(13,148,136,0.35) !important;
                        }
                        .fi-simple-page .fi-ac-btn-action.fi-btn:active {
                            transform: translateY(0) !important;
                        }
                        .fi-simple-page .fi-ac-btn-action.fi-btn .fi-icon { display: none !important; }

                        @media (max-width: 640px) {
                            .fi-simple-layout { padding: 0.75rem !important; }
                            .fi-simple-page { margin: 0.5rem !important; padding: 1.5rem !important; }
                            .fi-simple-page .fi-input { padding: 0.625rem 0.875rem 0.625rem 2.5rem !important; font-size: 1rem !important; }
                        }
                    </style>
                ')
            )
            ->renderHook(
                'panels::simple-layout.start',
                fn () => new HtmlString('
                    <div style="position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 0;">
                        <div style="position: absolute; top: -15%; right: -5%; width: 45%; height: 70%; background: radial-gradient(ellipse, rgba(13,148,136,0.1), transparent 70%); border-radius: 50%;"></div>
                        <div style="position: absolute; bottom: -10%; left: -5%; width: 50%; height: 55%; background: radial-gradient(ellipse, rgba(245,158,11,0.08), transparent 70%); border-radius: 50%;"></div>
                        <div style="position: absolute; top: 40%; left: 60%; width: 30%; height: 40%; background: radial-gradient(ellipse, rgba(59,130,246,0.05), transparent 70%); border-radius: 50%;"></div>
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
                'panels::simple-layout.end',
                fn () => new HtmlString('
                    <div style="text-align: center; padding: 1.5rem 1rem 2rem; font-size: 0.75rem; color: #9ca3af;">
                        <a href="'.route('home').'" style="color: #0d9488; text-decoration: none; font-weight: 500; display: inline-block; margin-bottom: 0.5rem;">← Back to Website</a>
                        <br>
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
