<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\CoachDomain;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Label84\HoursHelper\Facades\HoursHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Collection as SupportCollection;

class NewSports extends Component
{
    use LivewireAlert;

    public Collection $sports;
    public Array $days;

    public SupportCollection $listHoursSince;
    public SupportCollection $listHoursUntil;

    // dependensi form
    public $sport_id;
    public $working_days;
    public $working_time_start;
    public $working_time_end;
    public $price;

    protected $messages = [
        'sport_id.required' => 'Tidak boleh kosong',
        'sport_id' => 'Jenis olahraga tidak diketahui',

        'working_days.required' => 'Tidak boleh kosong',

        'working_time_start.required' => 'Tidak boleh kosong',
        'working_time_start.date_format' => 'Format waktu salah',

        'working_time_end.required' => 'Tidak boleh kosong',
        'working_time_end.date_format' => 'Format waktu salah',
        'working_time_end.after' => 'Waktu harus lebih besar dari jam mulai',

        'price.required' => 'Tidak boleh kosong',
        'price.numeric' => 'Harus numerik',
        'price.max' => 'Maximal 500.000',
    ];

    public function render()
    {
        if($this->sport_id == null)
        {
            $this->sport_id = 1;
        }

        if($this->working_days == null)
        {
            $this->working_days = "Senin";
        }

        $this->listHoursSince = HoursHelper::create("01:00", "23:00", 30);
        if($this->working_time_start == null)
        {
            $this->listHoursUntil = HoursHelper::create("01:00", "23:00", 30);
        }

        else
        {
            $util = Carbon::parse($this->working_time_start)->addMinutes(30)->format('H:i');
            $this->listHoursUntil = HoursHelper::create($util, "23:00", 30);
        }

        return view('livewire.new-sports');
    }

    public function rules()
    {
        return [
            'sport_id' => ['required', 'exists:sports,id'],
            'working_days' => ['required', Rule::in(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'])],
            'working_time_start' => ['required', 'date_format:H:i'],
            'working_time_end' => ['required', 'date_format:H:i', 'after:working_time_start'],
            'price' => ['required', 'numeric', 'max:1000000']
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function storeNewSport()
    {
        if(!Auth::guard('coach')->check())
        {
            return redirect()->route('coach.login.view');
        }

        $data = $this->validate();
        $data['coach_id'] = Auth::guard('coach')->id();

        $domains = CoachDomain::where('coach_id', Auth::guard('coach')->id())->get();

        if($domains->isEmpty())
        {
            CoachDomain::create($data);
            $this->flash('success', 'Kategori Olahraga Telah ditambahkan', [], route('coach.sports.index'));
            return;
        }

        $this->checkDomain($domains, $data);
    }

    private function checkDomain($domains, $data)
    {
        foreach($domains as $domain)
        {
            if($domain->working_days == $data['working_days']) // gaboleh ada 1 coach yang punya 2 job olahraga dalam 1 hari
            {
                $this->alert('info', "Sudah ada jadwal di hari {$data['working_days']}, pilih hari lain");
                return;
            }
        }

        CoachDomain::create($data);
        $this->flash('success', 'Kategori Olahraga Telah ditambahkan', [], route('coach.sports.index'));
    }
}
