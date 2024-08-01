<div class="p-20">
    <form action="" method="post" class="flex flex-col p-3 border-2 border-slate-400 rounded-md">
        @csrf
        <label class="text-gray-500" >Email:</label>
        <input type="text" name="email" value="">
        <label class="text-gray-500" >Password:</label>
        <input type="password" name="password" value="">
        <button class="bg-blue-200 mt-5 text-sky-600">Enviar</button>
        @if ($errors->any())
        <div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    </form>
</div>
