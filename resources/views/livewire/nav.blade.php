<nav class="bg-zinc-300 border border-gray-600 rounded-md h-full transition-all duration-300 "
 :class="isOpen ? 'w-8' : 'w-64'" >
 <form method="POST" action="{{ route('logout') }}" style="display: inline;">
        @csrf
        <button type="submit" class="bg-blue-500 text-white w-100% px-2 py-1 rounded-md mb-4">
            <i class="bi bi-person"></i>
        </button>
    </form>
    <button @click="isOpen = !isOpen"
        class="bg-blue-500 text-white w-100% px-2 py-1 rounded-md mb-4">
        <i class="bi bi-list"></i>
    </button>
    <ul class="{{config('tailwind.nav-list')}} " id="nav_home">
        <li class="{{config('tailwind.nav-item')}}"><a href="home" class="nav-link block"><i class="bi bi-house"></i><span :class="isOpen ? 'hidden' : 'inline'" > Home </span></a></li>
    </ul>
    <div><!-- class="bg-zinc-300 border border-gray-600" -->
        @foreach (session('permissions') as $permission)
            <div class="{{config('tailwind.nav-item')}} block text-black" ><a class="block" href="javascript:$('.navProg').hide(0);$('#nav_{{$permission['menu']}}').toggle(500);"><i class="{{$permission['icon_menu']}}"></i><span :class="isOpen ? 'hidden' : 'inline'" > {{$permission['menu_label']}}</span></a></div>
            <ul class="{{config('tailwind.nav-list')}} navProg hidden" id="nav_{{$permission['menu']}}">
                @foreach ($permission['routes'] as $route)
                    <li class="bg-gray-200 border border-zinc-300 px-2 py-1 "><a href="{{$route['route']}}" class="nav-link block"><i class="{{$route['icon_route']}}"></i><span :class="isOpen ? 'hidden' : 'inline'" > {{$route['route_label']}}</span></a></li>
                @endforeach
            </ul>
        @endforeach
    </div>    
</nav>