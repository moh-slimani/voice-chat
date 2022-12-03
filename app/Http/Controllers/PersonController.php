<?php

namespace App\Http\Controllers;

use App\Events\CallAnswered;
use App\Events\CallEnded;
use App\Events\CallRequested;
use App\Events\RemotePeerIceCandidateSent;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:' . Person::class,
            'gender' => 'required|string|max:1',
        ]);

        $person = Person::create([
            'username' => $request->get('username'),
            'gender' => $request->get('gender'),
            'last_seen' => now()
        ]);


        return redirect()->route('chat', ['username' => $person->username]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Person $person
     * @return \Inertia\Response
     */
    public function show(Person $person): \Inertia\Response
    {
        $randomPerson = Person::where('username', '!=', $person->username)
            ->inRandomOrder()
            ->first();
        return Inertia::render('Chat', compact('person', 'randomPerson'));
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Person $person
     * @return Collection|Person|null
     */
    public function refresh(Request $request, Person $person): \Illuminate\Support\Collection|Person|null
    {
        $randomPerson = Person::where('username', '!=', $person->username);

        if ($request->has('g')) {
            $randomPerson->where('gender', '=', $request->get('g'));
        }

        return $randomPerson->inRandomOrder()->first();
    }

    public function call(Request $request, Person $person)
    {
        $person->last_seen = now();
        $person->save();


        /** @var Person $from */
        $from = Person::where('username', $request->get('me'))->firstOrFail();

        $to = $person;

        info('offer from : ' . $from->username . ' for : ' . $to->username);

        event(new CallRequested($from, $to, $request->get('offer')));
        return null;
    }

    public function hangup(Request $request, Person $person)
    {
        $person->last_seen = now();
        $person->save();


        /** @var Person $from */
        $to = Person::where('username', $request->get('to'))->firstOrFail();

        $from = $person;

        info('call ended from : ' . $from->username . ' for : ' . $to->username);

        event(new CallEnded($from, $to));
        return null;
    }

    public function answer(Request $request, Person $person)
    {
        $person->last_seen = now();
        $person->save();


        /** @var Person $from */
        $from = Person::where('username', $request->get('me'))->firstOrFail();

        $to = $person;

        info('answer from : ' . $from->username . ' for : ' . $to->username);

        event(new CallAnswered($from, $to, $request->get('answer')));
        return null;
    }

    public function candidate(Request $request, Person $person)
    {

        $person->last_seen = now();
        $person->save();

        /** @var Person $from */
        $from = Person::where('username', $request->get('me'))->firstOrFail();

        $to = $person;

        info('candidate from : ' . $from->username . ' for : ' . $to->username);

        event(new RemotePeerIceCandidateSent($from, $to, $request->get('candidate')));
        return null;
    }

    /**
     * just tell the server you are still online.
     *
     * @param Person $person
     * @return null
     */
    public function checkin(Person $person)
    {
        $person->last_seen = now();
        $person->save();
        return null;
    }

    /**
     * just tell the server you are still laving.
     *
     * @param Person $person
     * @return RedirectResponse
     */
    public function checkout(Person $person): \Illuminate\Http\RedirectResponse
    {
        $person->delete();
        return redirect()->route('home');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Person $person
     * @return \Illuminate\Http\Response
     */
    public function edit(Person $person)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Person $person
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Person $person)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Person $person
     * @return \Illuminate\Http\Response
     */
    public function destroy(Person $person)
    {
        //
    }
}
