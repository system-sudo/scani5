@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('You are logged in!') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('scripts')


<script type="module">
    // Get the authenticated user's ID (passed from the backend)
    const userId = {{ auth()->id() }};

    console.log('user-id: ', userId);

    // Listen to the private channel for the authenticated user
    // window.Echo.private(`user.${userId}`)
    window.Echo.channel('posts')
        .listen('.create', (data) => {
            console.log('Notification receivedhaha: ', data);
            var d1 = document.getElementById('notification');
            d1.insertAdjacentHTML('beforeend', '<div class="alert alert-success alert-dismissible fade show"><span><i class="fa fa-circle-check"></i>  '+data.message+'</span></div>');
            d1.insertAdjacentHTML('beforeend', '<div class="alert alert-success alert-dismissible fade show"><span><i class="fa fa-circle-check"></i>  '+data.post.type+'</span></div>');
        });
</script>

@endsection
