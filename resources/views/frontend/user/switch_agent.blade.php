@extends('frontend.layouts.login')
@section('title')
    Switch Agent | Fisherly
@endsection
@section('body-classes') loginPage @endsection
@push('after-styles')
    <style>
        .error-help-block{
            color: #ff0000;
        }
    </style>
@endpush
@section('login-section')
<div class="box-login--signup box-login__user">
        <h3 style="font-size:25px">Switch Agent</h3>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
       <p>You are already signed up with <strong>{{$primaryAgent->fname}} {{$primaryAgent->lname}}.</strong></p>
       <p>Do you want to switch your agent to <strong>{{$agent->fname}} {{$agent->lname}}</strong>?</p>
       <div class="col-md-12">
        <div class="col-xs-12">
            <button type="button" class="btn btn-danger confirm" data-val="no" style="font-size:11px">Proceed with {{$primaryAgent->fname}} {{$primaryAgent->lname}}</button>
       </div>
           <div class="col-xs-12" style="margin-top:50px; font-size:14px">
            <input type="checkbox" id="confirm_check"> I confirm I want to switch to {{$agent->fname}} {{$agent->lname}}
            </div>
           <div class="col-xs-12">
            <button type="button" class="btn confirm" data-val="yes" id="switch-btn" style="font-size:11px;" disabled>Switch to {{$agent->fname}} {{$agent->lname}}</button>
       </div>
       </div>    
</div>
@endsection
@push('after-scripts')
<script>
    jQuery(document).ready(function(){
        var val="";
        jQuery(".confirm").on('click', function(){
            val = jQuery(this).data('val');
            jQuery.ajax({
                method: "post",
                url: "{{route('confirm_switch_agent')}}?confirm="+val,
                data: {"_token": "{{ csrf_token() }}"}
            }).done(function(response){
                if(val == 'yes'){
                    document.location = "{{$next_url}}";
                }
                else{
                    document.location = "{{$next_url_no}}";
                }
            });
        });

        jQuery("#confirm_check").on('click', function(){
            if($(this).is(":checked")){
                $("#switch-btn").prop("disabled", false);
            }
            else{
                $("#switch-btn").prop("disabled", true);
            }
        });

    });
</script>
@endpush