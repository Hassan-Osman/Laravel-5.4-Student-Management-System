<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Academic;
use App\Program;
use App\Level;
use App\Shift;
use App\Time;
use App\Batch;
use App\Group;
use App\MyClass;
use App\Status;
use App\Fee;
use App\Receipt;
use App\Transaction;
use App\StudentFee;
use App\ReceiptDetail;

class FeeController extends Controller
{
    public function getPayment()
    {
        return view('fee.searchPayment');
    }

    public function studentStatus($student_id)
    {
        return Status::latest('statuses.status_id')
                    ->join('students', 'students.student_id', '=', 'statuses.student_id')
                    ->join('classes', 'classes.class_id', '=', 'statuses.class_id')
                    ->join('academics', 'academics.academic_id', '=', 'classes.academic_id')
                    ->join('shifts', 'shifts.shift_id', '=', 'classes.shift_id')
                    ->join('times', 'times.time_id', '=', 'classes.time_id')
                    ->join('groups', 'groups.group_id', '=', 'classes.group_id')
                    ->join('batches', 'batches.batch_id', '=', 'classes.batch_id')
                    ->join('levels', 'levels.level_id', '=', 'classes.level_id')
                    ->join('programs', 'programs.program_id', '=', 'levels.program_id')
                    ->where('students.student_id', $student_id);
    }

    public function showSchoolFee($level_id)
    {
        return Fee::join('academics', 'academics.academic_id', '=', 'fees.academic_id')
            ->join('levels', 'levels.level_id', '=', 'fees.level_id')
            ->join('programs', 'programs.program_id', '=', 'levels.program_id')
            ->join('feetypes', 'feetypes.fee_type_id', '=', 'fees.fee_type_id')
            ->where('levels.level_id', $level_id)
            ->orderBy('fees.amount', 'DESC');
    }

    public function payment($viewName, $student_id)
    {
        $status = $this->studentStatus($student_id)->first();
        $programs = Program::where('program_id', $status->program_id)->get();
        $levels = Level::where('program_id', $status->program_id)->get();
        $studentfee = $this->showSchoolFee($status->level_id)->first();
        $receipt_id = ReceiptDetail::where('student_id', $student_id)->max('receipt_id');

        return view($viewName, compact('status', 'programs', 'levels', 'studentfee', 'receipt_id'))->with('student_id', $student_id);
    }

    public function showStudentPayment(Request $request)
    {
        $student_id = $request->student_id;
        return $this->payment('fee.payment', $student_id);
    }

    public function goPayment($student_id)
    {
        return $this->payment('fee.payment', $student_id);
    }

    public function savePayment(Request $request)
    {
        $studentFee  = StudentFee::create($request->all());

        $transaction = Transaction::create([
            'fee_id'        => $request->fee_id,
            'transact_date' => $request->transact_date,
            'user_id'       => $request->user_id,
            'student_id'    => $request->student_id,
            's_fee_id'      => $studentFee->s_fee_id,
            'paid'          => $request->paid,
            'remark'        => $request->remark,
            'description'   => $request->description,
        ]);
        $receipt_id = Receipt::autoNumber();

        ReceiptDetail::create([
            'receipt_id'   => $receipt_id,
            'student_id'   => $request->student_id,
            'transact_id'  => $transaction->transact_id,
        ]);

        return back();

    }
}