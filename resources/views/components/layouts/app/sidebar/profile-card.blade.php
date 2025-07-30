<div class="flex items-center cursor-pointer p-4" type="button" data-dropdown-toggle="userDropdown" data-dropdown-placement="top-start">
    <div class=" flex justify-between text-sm font-normal">
        <img class="size-10 rounded-full" src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="user photo">
        <div class="ms-4">
            <div class="text-sm font-semibold text-gray-900 ">{{$user->name}}</div>
            <div class="text-sm font-normal">{{$user->myRole()}}</div>
        </div>


    </div>
</div>




<!-- Dropdown menu -->
<div id="userDropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-52">
    <div class="px-4 py-3 text-sm text-gray-900 dark:text-white">
        <div>{{$user->name}}</div>
        <div class="font-medium truncate">{{$user->email}}</div>
    </div>
    <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="avatarButton">
        <li>
            <a href="{{route('settings.profile')}}" class="block px-4 py-2 hover:bg-gray-100">@lang('general.my-account')</a>
        </li>

    </ul>
    <div class="py-1">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class=" block w-full px-4 text-start py-2 text-sm text-gray-700 hover:bg-gray-100 ">@lang('auth.logout')</button>
        </form>
    </div>
</div>



