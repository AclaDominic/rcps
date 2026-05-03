<x-filament-breezy::auth-card action="authenticate">

    <div class="w-full flex flex-col items-center space-y-2">
        <div class="w-56 h-32  filament-login-logo"></div>
         {{-- <x-filament::brand /> --}}
    </div>

    <h2 class="font-bold tracking-tight text-center text-2xl">
        {{ __('filament::login.heading') }}
    </h2>

    {{-- @if(session()->has('oidc_error'))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
            <span class="font-medium">{{ __('OIDC Connect error') }}</span> {{ __('Invalid account!') }}
        </div>
    @endif --}}

    @if(config('system.login_form.is_enabled'))
        {{-- <div>
            @if(config("filament-breezy.enable_registration"))
                <p class="mt-2 text-sm text-center">
                    {{ __('filament-breezy::default.or') }}
                    <a class="text-primary-600" href="{{route(config('filament-breezy.route_group_prefix').'register')}}">
                        {{ strtolower(__('filament-breezy::default.registration.heading')) }}
                    </a>
                </p>
            @endif
        </div> --}}

        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            {{ __('filament::login.buttons.submit.label') }}
        </x-filament::button>

        <div class="text-center">
            <a class="text-primary-600 hover:text-primary-700" href="{{route(config('filament-breezy.route_group_prefix').'password.request')}}">{{ __('filament-breezy::default.login.forgot_password_link') }}</a>
        </div>
    @endif

   

</x-filament-breezy::auth-card>
