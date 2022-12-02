<?php

namespace App\Http\Controllers;

use App\Events\CallAnswered;
use App\Events\CallRequested;
use App\Events\RemotePeerIceCandidateSent;
use App\Models\Person;
use Illuminate\Http\Request;
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
        $randomPerson = Person::all()->where('username', '!=', $person->username)->random();
        return Inertia::render('Chat', compact('person', 'randomPerson'));
    }

    public function call(Request $request, Person $person)
    {

        /** @var Person $from */
        $from = Person::where('username', $request->get('me'))->firstOrFail();

        info($from);

        $to = $person;

        info('offer from : ' . $from->username . ' for : ' . $to->username);

        event(new CallRequested($from, $to, $request->get('offer')));
        return null;
    }

    public function answer(Request $request, Person $person)
    {

        /** @var Person $from */
        $from = Person::where('username', $request->get('me'))->firstOrFail();

        info($from);

        $to = $person;

        info('answer from : ' . $from->username . ' for : ' . $to->username);

        event(new CallAnswered($from, $to, $request->get('answer')));
        return null;
    }

    public function candidate(Request $request, Person $person)
    {

        /** @var Person $from */
        $from = Person::where('username', $request->get('me'))->firstOrFail();

        info($from);

        $to = $person;

        info('answer from : ' . $from->username . ' for : ' . $to->username);

        event(new RemotePeerIceCandidateSent($from, $to, $request->get('candidate')));
        return null;
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
