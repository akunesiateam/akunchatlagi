@foreach($groups as $group)
    @include("scribe::themes.tailwind.group", ['group' => $group])
@endforeach
