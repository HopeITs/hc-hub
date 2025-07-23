@props([
    'id' => 'profile-dropdown'
])
<button data-dropdown-toggle="{{$id}}" class="flex items-center justify-center" type="button">
									<span class="relative">
										<img src="assets/img/profiles/avatar-12.jpg" alt="Img"
                                             class="size-6 rounded-full">
										<span
                                            class="right-0 bottom-0 absolute  w-2 h-2 bg-success border-2 border-white  rounded-full"></span>
									</span>
</button>
<div id="{{$id}}" class="z-10 hidden bg-white divide-y divide-borderColor rounded-[5px] shadow w-55">
    <div class="px-[20px] py-4">
        <div class="flex items-center">
												<span class="size-[45px] me-2">
													<img src="assets/img/profiles/avatar-12.jpg" alt="img"
                                                         class="rounded-full border-2 border-gray-100">
												</span>
            <div>
                <h5 class="mb-0">{{$this->user->name}}</h5>
                <p class="text-xs leading-normal text-gray-900 font-medium mb-0">{{$this->user->email}}</p>
            </div>
        </div>
    </div>
    <ul class="p-[20px]">
        {{$slot}}
    </ul>
    <div class="px-[20px] py-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center text-gray-900 py-2 hover:text-primary">
                <i class="ti ti-login me-2"></i> @lang('auth.logout')
            </button>
        </form>
    </div>
</div>
