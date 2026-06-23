<x-mail::message>
# Payslip Available

Hi {{ $employeeName }},

Your payslip for **{{ $period }}** is now available.

**Net Pay: ₱{{ $netPay }}**

Your payslip PDF is attached to this email. You can also view it in the HRIS portal.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
