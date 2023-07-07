<x-mail::message>
# Password Reset

Click below to reset your password

<x-mail::button :url="$url">
Password Reset
</x-mail::button>

If the button doesn't works, copy the link and paste it into the addres bar<br>
<a :href="$url" target="_blank">{{ $url }}</a>

Thanks,<br>
Andersen
</x-mail::message>
