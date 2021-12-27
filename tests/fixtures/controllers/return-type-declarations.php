<?php

namespace App\Http\Controllers;

use App\Http\Requests\TermStoreRequest;
use App\Http\Requests\TermUpdateRequest;
use App\Models\Term;
use Illuminate\Http\Request;

class TermController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): \Illuminate\Http\Response
    {
        $terms = Term::all();

        return view('term.index', compact('terms'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request): \Illuminate\Http\Response
    {
        return view('term.create');
    }

    /**
     * @param \App\Http\Requests\TermStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TermStoreRequest $request): \Illuminate\Http\Response
    {
        $term = Term::create($request->validated());

        $request->session()->flash('term.id', $term->id);

        return redirect()->route('term.index');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Term $term
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Term $term): \Illuminate\Http\Response
    {
        return view('term.show', compact('term'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Term $term
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Term $term): \Illuminate\Http\Response
    {
        return view('term.edit', compact('term'));
    }

    /**
     * @param \App\Http\Requests\TermUpdateRequest $request
     * @param \App\Models\Term $term
     * @return \Illuminate\Http\Response
     */
    public function update(TermUpdateRequest $request, Term $term): \Illuminate\Http\Response
    {
        $term->update($request->validated());

        $request->session()->flash('term.id', $term->id);

        return redirect()->route('term.index');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Term $term
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Term $term): \Illuminate\Http\Response
    {
        $term->delete();

        return redirect()->route('term.index');
    }
}
