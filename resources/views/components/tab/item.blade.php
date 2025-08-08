@props(['title' , 'activeTab' => 'test'])
<li>
    <button {{$attributes->merge([
    'type' => 'button',
    'class' => 'w-full flex items-center p-3 rounded-lg'
])}}

            @click="activeTab = '{{$activeTab}}'"
            :class="{ 'bg-primary text-white': activeTab === '{{$activeTab}}', 'bg-gray-50 hover:bg-gray-100': activeTab !== '{{$activeTab}}' }">
        {{$slot}}
        <span class="ms-2 font-semibold">{{$title}}</span>
    </button>
</li>
