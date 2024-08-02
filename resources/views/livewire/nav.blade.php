<nav class="{{config('tailwind.navbar')}}">
    <ul class="{{config('tailwind.nav-list')}}">
        <li class="{{config('tailwind.nav-item')}}"><a href="home" class="nav-link">Home</a></li>
        <li class="{{config('tailwind.nav-item')}}"><a href="users" class="nav-link">Users</a></li>
        <li class="{{config('tailwind.nav-item')}}"><a href="sectors" class="nav-link">Sectors</a></li>
        <li class="{{config('tailwind.nav-item')}}"><a href="permissions" class="nav-link">Permissions</a></li>
        <li class="{{config('tailwind.nav-item')}}"><a href="suppliers" class="nav-link">Suppliers</a></li>
        <li class="{{config('tailwind.nav-item')}}">
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="{{config('nav-item-logout')}}">Logout</button>
            </form>
        </li>
    </ul>
</nav>