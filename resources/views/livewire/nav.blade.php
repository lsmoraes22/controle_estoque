<nav class="navbar">
    <ul class="nav-list">
        <li class="nav-item"><a href="home" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="users" class="nav-link">Users</a></li>
        <li class="nav-item"><a href="sectors" class="nav-link">Sectors</a></li>
        <li class="nav-item"><a href="permissions" class="nav-link">Permissions</a></li>
        <li class="nav-item"><a href="contact" class="nav-link">Contact</a></li>
        <li class="nav-item">
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="nav-link text-blue-500">Logout</button>
            </form>
        </li>
    </ul>
</nav>