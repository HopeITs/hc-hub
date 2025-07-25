<x-layouts.app :title="__('Dashboard')">


 <livewire:city-table/>
@section('js')
        <!-- Datatable JS -->
        <script src="{{asset('assets/js/dataTables.js')}}"></script>
        <script src="{{asset('assets/js/dataTables.tailwindcss.js')}}"></script>
    @endsection
</x-layouts.app>
<!-- /Main Wrapper -->

