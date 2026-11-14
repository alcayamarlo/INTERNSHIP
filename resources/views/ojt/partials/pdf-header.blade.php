{{-- School header for printed OJT forms. Expects $header from App\Support\OjtFormHeader::data(); a seal prints only when its file exists. --}}
<style>
    .ojt-head { width: 100%; border-collapse: collapse; }
    .ojt-head td { vertical-align: middle; }
    .ojt-head .seal { width: 74px; text-align: center; }
    .ojt-head .seal img { width: 66px; height: 66px; }
    .ojt-head .school { text-align: center; }
    .ojt-head .school .name { font-size: 18px; font-weight: bold; letter-spacing: 0.5px; }
    .ojt-head .school .sub { font-size: 8.5px; margin-top: 2px; }
    .ojt-rule-orange { border-top: 3px solid #f59e0b; margin-top: 8px; }
    .ojt-rule-red { border-top: 2px solid #c8102e; margin-top: 2px; }
</style>
<table class="ojt-head">
    <tr>
        @if($header['school_seal'])<td class="seal"><img src="{{ $header['school_seal'] }}" alt=""></td>@endif
        <td class="school">
            <div class="name">{{ $header['school_name'] }}</div>
            <div class="sub">{{ $header['school_supervision'] }}</div>
            <div class="sub">{{ $header['school_address'] }}</div>
        </td>
        @if($header['college_seal'])<td class="seal"><img src="{{ $header['college_seal'] }}" alt=""></td>@endif
    </tr>
</table>
<div class="ojt-rule-orange"></div>
<div class="ojt-rule-red"></div>
