<nav class="bg-zinc-300 border border-gray-600 h-full transition-all duration-300 "
 :class="isOpen ? 'w-8' : 'w-64'" >
    <button @click="isOpen = !isOpen"
        class="bg-blue-500 text-white w-100% px-2 py-1 rounded-md mb-4">
        <i class="bi bi-list"></i>
    </button>
    <ul class="{{config('tailwind.nav-list')}}">    
        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
            @csrf
            <button type="submit" class="bg-blue-500 text-white w-100% px-2 py-1 rounded-md mb-4">
                <i class="bi bi-person"></i>
            </button>
        </form>
        <li class="{{config('tailwind.nav-item')}}"><a href="home" class="nav-link"><i class="bi bi-house"></i><span :class="isOpen ? 'hidden' : 'inline'" > Home </span></a></li>
        <li class="{{config('tailwind.nav-item')}}"><a href="users" class="nav-link"><i class="bi bi-person"></i><span :class="isOpen ? 'hidden' : 'inline'" > Users </span></a></li>
        <li class="{{config('tailwind.nav-item')}}"><a href="sectors" class="nav-link"><i class="bi bi-door-open"></i><span :class="isOpen ? 'hidden' : 'inline'" >  Sectors</span></a></li>
        <li class="{{config('tailwind.nav-item')}}"><a href="permissions" class="nav-link"><i class="bi bi-key"></i><span :class="isOpen ? 'hidden' : 'inline'" > Permissions</span></a></li>
        <li class="{{config('tailwind.nav-item')}}"><a href="suppliers" class="nav-link"><i class="bi bi-archive"></i><span :class="isOpen ? 'hidden' : 'inline'" > Suppliers</span></a></li>
    </ul>
</nav>