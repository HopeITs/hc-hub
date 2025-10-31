@props(['active' => false , 'activeTab' => 'all'])

<li class="me-2">
     <button type="button"
        {{$attributes->merge([
     'class' =>  $active ?  'inline-block px-4 py-3 rounded-lg text-white bg-primary' : 'inline-block px-4 py-3 rounded-lg hover:text-gray-900 hover:bg-gray-100'
 ])}}
        wire:click="setActiveTab('{{$activeTab}}')">
        {{$slot}}
     </button>
</li>
