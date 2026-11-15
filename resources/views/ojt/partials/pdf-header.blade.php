{{--
    School header for printed OJT forms. Expects $header from App\Support\OjtFormHeader::data().
    School seal on the left, College of IT seal on the right; a seal prints only when its file exists,
    and both slots keep their width so the school name stays centred.
--}}
<style>
    .ojt-head { width: 100%; border-collapse: collapse; }
    .ojt-head td { vertical-align: middle; padding: 0; }
    .ojt-head .seal { width: 74px; text-align: center; }
    .ojt-head .seal img { width: 64px; height: 64px; }
    .ojt-head .school { text-align: center; font-family: Helvetica, Arial, sans-serif; }
    .ojt-head .school .name { font-size: 17px; font-weight: bold; letter-spacing: 0.3px; }
    .ojt-head .school .sub { font-size: 8.5px; margin-top: 2px; }
    .ojt-rule-orange { border-top: 4px solid #E8751A; margin-top: 7px; }
    .ojt-rule-red { border-top: 1px solid #c8102e; margin-top: 2px; }
</style>
<table class="ojt-head">
    <tr>
        <td class="seal">@if($header['school_seal'])<img src="{{ $header['school_seal'] }}" alt="">@endif</td>
        <td class="school">
            <div class="name">{{ $header['school_name'] }}</div>
            <div class="sub">{{ $header['school_supervision'] }}</div>
            <div class="sub">{{ $header['school_address'] }}</div>
        </td>
        <td class="seal">@if($header['college_seal'])<img src="{{ $header['college_seal'] }}" alt="">@endif</td>
    </tr>
</table>
<div class="ojt-rule-orange"></div>
<div class="ojt-rule-red"></div>
