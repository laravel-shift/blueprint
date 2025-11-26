<?php

namespace App\Http\Controllers;

use App\Http\Controller\VehicleController;
use App\Http\Requests\CarStoreRequest;
use App\Http\Requests\CarUpdateRequest;
use App\Http\Resources\CarCollection;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Traits\HandlesHighSpeeds;
use App\Traits\Hijackable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CarController extends VehicleController
{
    use HandlesHighSpeeds, Hijackable;

    public function index(Request $request): CarCollection
    {
        $cars = Car::all();

        return new CarCollection($cars);
    }

    public function store(CarStoreRequest $request): CarResource
    {
        $car = Car::create($request->validated());

        return new CarResource($car);
    }

    public function show(Request $request, Car $car): CarResource
    {
        return new CarResource($car);
    }

    public function update(CarUpdateRequest $request, Car $car): CarResource
    {
        $car->update($request->validated());

        return new CarResource($car);
    }

    public function destroy(Request $request, Car $car): Response
    {
        $car->delete();

        return response()->noContent();
    }
}
