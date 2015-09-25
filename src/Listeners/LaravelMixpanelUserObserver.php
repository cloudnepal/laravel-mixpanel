<?php namespace GeneaLabs\LaravelMixpanel\Listeners;

use GeneaLabs\LaravelMixpanel\LaravelMixpanel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LaravelMixpanelUserObserver
{
    protected $mixPanel;
    protected $request;

    /**
     * @param LaravelMixpanel $mixPanel
     */
    public function __construct(LaravelMixpanel $mixPanel)
    {
        $this->mixPanel = $mixPanel;
    }

    /**
     * @param Model $user
     */
    public function created(Model $user)
    {
        $firstName = $user->first_name ?: '';
        $lastName = $user->last_name ?: '';

        if ($user->name) {
            $nameParts = explode(' ', $user->name);
            array_filter($nameParts);
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);
        }

        $data[] = [
            '$first_name' => $firstName,
            '$last_name' => $lastName,
        ];

        if ($user->created_at) {
            $data['$created'] = $user->created_at->format('Y-m-d\Th:i:s');
        }

        array_filter($data);

        $request = App::make(Request::class);
        $this->mixPanel->people->set($user->id, $data, $request->ip());
        $this->mixPanel->track('User', ['Status' => 'Registered']);
    }

    /**
     * @param Model $user
     */
    public function saving(Model $user)
    {
        $this->mixPanel->identify($user->id);
        $data = [];
        $firstName = $user->first_name ?: '';
        $lastName = $user->last_name ?: '';

        if ($user->name) {
            $nameParts = explode(' ', $user->name);
            array_filter($nameParts);
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);
        }

        $data[] = [
            '$first_name' => $firstName,
            '$last_name' => $lastName,
            '$email' => $user->email,
        ];

        if ($user->created_at) {
            $data['$created'] = $user->created_at->format('Y-m-d\Th:i:s');
        }

        array_filter($data);

        if (count($data)) {
            $request = App::make(Request::class);
            $this->mixPanel->people->set($user->id, $data, $request->ip());
        }
    }

    /**
     * @param Model $user
     */
    public function deleting(Model $user)
    {
        $this->mixPanel->identify($user->id);
        $this->mixPanel->track('User', ['Status' => 'Deactivated']);
    }

    /**
     * @param Model $user
     */
    public function restored(Model $user)
    {
        $this->mixPanel->identify($user->id);
        $this->mixPanel->track('User', ['Status' => 'Reactivated']);
    }
}