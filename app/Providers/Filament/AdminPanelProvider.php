<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use DutchCodingCompany\FilamentSocialite\Provider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ->plugins([
                FilamentShieldPlugin::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        userMenuLabel: 'My Profile', // Customizes the 'account' link label in the panel User Menu (default = null)
                        shouldRegisterNavigation: true, // Adds a main navigation item for the My Profile page (default = false)
                        navigationGroup: 'Settings', // Sets the navigation group for the My Profile page (default = null)
                        hasAvatars: false, // Enables the avatar upload form component (default = false)
                        slug: 'my-profile' // Sets the slug for the profile page (default = 'my-profile')
                    ),
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google')
                            ->color(Color::hex('#4285f4')),
                    ])
                    ->registration(true)
                    ->rememberLogin(true)
                    ->createUserUsing(function (\Laravel\Socialite\Contracts\User $oauthUser, string $provider) {
                        // Check if socialite user already exists
                        $existingSocialiteUser = \App\Models\SocialiteUser::where('provider', $provider)
                            ->where('provider_id', $oauthUser->getId())
                            ->first();

                        if ($existingSocialiteUser) {
                            return $existingSocialiteUser->user;
                        }

                        // Check if user exists by email
                        $existingUser = \App\Models\User::where('email', $oauthUser->getEmail())->first();

                        if ($existingUser) {
                            // Link existing user to social account
                            \App\Models\SocialiteUser::create([
                                'user_id' => $existingUser->id,
                                'provider' => $provider,
                                'provider_id' => $oauthUser->getId(),
                            ]);

                            return $existingUser;
                        }

                        // Create new user
                        $user = \App\Models\User::create([
                            'name' => $oauthUser->getName(),
                            'email' => $oauthUser->getEmail(),
                            'email_verified_at' => now(),
                        ]);

                        \App\Models\SocialiteUser::create([
                            'user_id' => $user->id,
                            'provider' => $provider,
                            'provider_id' => $oauthUser->getId(),
                        ]);

                        return $user;
                    })
                    ->resolveUserUsing(function (\Laravel\Socialite\Contracts\User $oauthUser, string $provider) {
                        $socialiteUser = \App\Models\SocialiteUser::where('provider', $provider)
                            ->where('provider_id', $oauthUser->getId())
                            ->first();

                        return $socialiteUser?->user;
                    })
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s');
    }
}
