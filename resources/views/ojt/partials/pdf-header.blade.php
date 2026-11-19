{{--
    School header for printed OJT forms. Expects $header from App\Support\OjtFormHeader::data().
    The two seals sit right beside the school name, on the same line and centred on the text: the school seal on
    its left, the College of IT seal on its right, 70px each. The forms are made with Dompdf, which has no
    flexbox, so this is a shrink-to-fit table centred on the page (the same picture as flex with a 16px gap).
    A seal prints only when its file exists; its slot keeps the width so the school name stays centred.
--}}
<style>
    .ojt-head { margin: 0 auto; border-collapse: collapse; }
    .ojt-head td { vertical-align: middle; padding: 0; }
    .ojt-head .seal { width: 70px; text-align: center; }
    .ojt-head .seal img { width: 70px; height: 70px; }
    .ojt-head .school { text-align: center; font-family: Helvetica, Arial, sans-serif; padding: 0 16px; white-space: nowrap; }
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
