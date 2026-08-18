<div id="lw-contianer-234hsdkfh" class="container-fluid panel-body" style="padding-top:80px;">
    @if($isConfirmed)

    <button class="btn btn-default" wire:click="togglePolling();runDeduplication();">@if($fetching) Running Deduplication... @else Run Deduplication @endif</button>
    <button class="btn btn-default" wire:click="refreshUpdated">@if($fetching) Refreshing Updated @else Refresh Updated @endif</button>
    <button class="btn btn-default" wire:click="togglePolling">{{ $isPolling ? 'Pause' : 'Resume' }}</button>
    <!-- <button class="btn btn-default" onclick="var el=document.querySelector('#lw-contianer-234hsdkfh');el.getAttribute('wire:id')).stopPolling();el.removeAttribute('wire:poll')">STOP</button> -->
    <label class="form-group form-inline">BatchSize: <input type="number" class="form-control" wire:model="batchSize" step="10" min="1" max="50000"></label>

    <div class="var-stats">
        <span class="label {{($updated==$expectedUpdates)?'btn-success':'btn-info'}}" id="var-stats-Updated">Updated: <span class="badge">{{$updated}}</span></span>
        <span class="label {{($uniques==$expectedUniques)?'btn-success':'btn-info'}}" id="var-stats-Uniques">Uniques: <span class="badge">{{$uniques}}</span></span>
        <span class="label btn-info" id="var-stats-BatchSize">BatchSize: <span class="badge">{{$batchSize}}</span></span>
        <span class="label btn-warning" id="var-stats-expectedUpdates">Expected Updates: <span class="badge">{{$expectedUpdates??''}}</span></span>
        <span class="label btn-warning" id="var-stats-expectedUniques">Expected Uniques: <span class="badge">{{$expectedUniques??''}}</span></span>
        <p>Updated: {{$updated}} | {{count($status)}} </p>
    </div>



    <div wire:poll.3s="runDeduplication" id="lw-status-updates" style="background:#eee9;max-height:800px;height:70vh;overflow:auto; padding:1em;">
        {{$fetching}}
        @foreach (array_reverse($status) as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>

    @else

    <!-- Trigger button -->
    <button type="button" class="btn btn-lg btn-danger center-block" data-toggle="modal" data-target="#confirmModalDD78cgw">Start Deduplication</button>
    
    <!-- Modal -->
    <div class="modal fade" id="confirmModalDD78cgw" tabindex="-1" role="dialog" aria-labelledby="confirmModalDD78cgwLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close pull-right" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h5 class="modal-title" id="confirmModalDD78cgwLabel">Warning!</h5>
                </div>
                <div class="modal-body">
                This is Irreversible process. This will delete records from the DB.
                <br />
                Are you sure you want to proceed?
            </div>
            <div class="modal-footer">
                    <button type="button" class="btn btn-primary"   data-dismiss="modal" wire:click="toggleConfirmed();">{{$isConfirmed?'Confirmed':'I Understand!'}}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>


    @endif

    <style>
    /* .drawer-nav{visibility:hidden;} */
    #lw-status-updates {display: flex;flex-direction: column;gap: 3px;font-family: system-ui; border-radius:4px;}
    #lw-status-updates > div {padding:2px 4px; border-radius:4px;}
    #lw-status-updates div:nth-child(even) {background: #8883;}
    </style>
    
    <script>
        Livewire.on('statusUpdated', (text) => {
            let elm = document.querySelector('#lw-status-updates');
            elm.innerHTML = (text+`<br>`+elm.innerHTML);
            // elm.scrollTo(0, elm.scrollHeight);
        });
    </script>
    <script>
        // document.addEventListener('DOMContentLoaded', function() {
        // });
    </script>

</div>
