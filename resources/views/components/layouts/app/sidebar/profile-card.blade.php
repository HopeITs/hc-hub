@auth
    <div class="flex items-center cursor-pointer p-4" type="button" data-dropdown-toggle="userDropdown"
        data-dropdown-placement="top-start">
        <div class="flex justify-between text-sm font-normal">
            @if (Auth::user())
                <img class="size-10 rounded-full" src="https://flowbite.com/docs/images/people/profile-picture-5.jpg"
                    alt="user photo">
                <div class="ms-4">
                    <div class="text-sm font-semibold text-gray-900">{{ Auth::user()->profile->name }}</div>
                    <div class="text-sm font-normal">{{ Auth::user()->myRole() }}</div>
                </div>
            @endif
        </div>
    </div>

    <div id="userDropdown" class="z-10 hidden bg-gray-100 divide-y divide-gray-100 rounded-lg shadow-md w-52">
        @if (Auth::user())
            <div class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                <div>{{ Auth::user()->profile->name }}</div>
                <div class="font-medium truncate">{{ Auth::user()->email }}</div>
            </div>
            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="avatarButton">
                <li>
                    <a href="{{ route('settings.profile') }}"
                        class="block px-4 py-2 hover:bg-gray-100">@lang('general.my-account')</a>
                </li>

                <li>
                    @php
                        $currentLocale = app()->getLocale();
                        $switchTo = $currentLocale === 'ar' ? 'en' : 'ar';
                        $localizedURL = LaravelLocalization::getLocalizedURL($switchTo, null, [], true);
                    @endphp
                    <a href="{{ $localizedURL }}" class="block px-4 py-2 hover:bg-gray-100 transition">
                        {{ $switchTo === 'ar' ? 'العربية' : 'English' }}
                    </a>
                </li>
            </ul>
            <div class="py-1 px-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="block w-full text-start py-2 text-sm text-gray-700 hover:bg-gray-100">@lang('auth.logout')</button>
                </form>
            </div>
        @endif
    </div>
@endauth

@guest
    <div class="p-4 space-y-2">
        <a href="{{ route('login') }}"
            class="block w-full text-center py-2 px-4 text-sm font-semibold text-white bg-primary hover:opacity-90 rounded-md">@lang('auth.login')</a>
        <a href="{{ route('register') }}"
            class="block w-full text-center py-2 px-4 text-sm font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md">@lang('auth.register')</a>
    </div>
@endguest
