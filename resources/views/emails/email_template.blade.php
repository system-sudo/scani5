<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template</title>
    <style>
        .mail-bgimage {
            background-image: url('{{ asset("/images/scani5_watermark1.png")}}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 300px 300px;
        }
    </style>
</head>


<body style="margin:0; padding:0; font-family: Arial, sans-serif;  color: #333333; ">

    <div
        style="width:100%; max-width:600px; margin:0 auto; background-color:#ffffff; box-shadow:0 0 10px rgba(0, 0, 0, 0.1);">

        <div style="text-align:center; background:#7A69EE; padding:10px; border-radius:8px 8px 0 0;">
            <div style="display:inline-flex; align-items:center;">

                @isset($data['orgLogo'])
                    <img src="{{ asset($data['orgLogo']) }}" alt="logo" width="30" style="margin-right:10px;">
                @endisset
                <h3 style="color:#ffffff; margin:0;">{{$data['org_name']}}</h3>
            </div>
        </div>

        <!-- Content Section -->
        <div style="padding:30px;" class="mail-bgimage">
            <h1 style="font-size:16px;">Dear {{ $data['recipientName'] }},</h1>
            @isset($data['bodyText'])
                <p style="font-size:14px; line-height:24px; margin-bottom:10px;">{!! nl2br($data['bodyText']) !!}</p>
            @endisset

            @isset($data['inviteLink'])
                <div style="text-align:center;">
                    <a href="{{$data['inviteLink']}}" target="_blank"
                        style="background-color:#7A69EE; padding:10px 20px; color:#ffffff; text-decoration:none; border-radius:3px; display:inline-block;">{{$data['buttonText']}}
                    </a>
                </div>
            @endisset

            <p style="font-size:14px; line-height:24px; margin-bottom:10px;">Thank you for using our application!</p>
            <p style="font-size:14px; line-height:24px; margin-bottom:10px;">Regards,</p>
            <p style="font-size:14px; line-height:24px; margin-bottom:10px;">{{ $data['senderName'] }}</p>

            @isset($data['inviteLink'])
                <p style="font-size:14px; line-height:24px; margin-bottom:10px;">If you're having trouble clicking the
                    "<strong>{{$data['buttonText']}}</strong>" button, you can copy and paste the URL below into your web
                    browser to {{Str::lower($data['buttonText'])}}:</p>
                <a href="{{ $data['inviteLink'] }}" target="_blank" rel="noopener noreferrer"
                    style="font-size:14px; line-height:24px; word-break:break-all; color:#2d4687;">{{ $data['inviteLink'] }}</a>
            @endisset

            @isset($data['bodyText'])
                <p style="font-size:14px; line-height:24px; margin-bottom:10px;">If you have any questions or need further
                    information, please do not hesitate to contact us at to <a href="#">{{config('custom.support_email')}}</a></p>
            @endisset
        </div>
        <!-- Footer Section -->
        <div
            style="text-align:center; padding:15px; font-size:12px; color:#fff; background-color:#7A69EE; border-radius:0 0 8px 8px;">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>

</body>

</html>
