<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class PrestataireController extends Controller
{

    public function show()
    {
        return response()->json(Auth::user());
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'required|string',
        ]);

        $user = Auth::user();
        $user->update($validatedData);

        return response()->json(['message' => 'Profile updated successfully'], 200);
    }
    public function getMyAnnonces()
    {

        $annonces = auth()->user()->annonces;

        $formattedAnnonces = $annonces->map(function ($annonce) {
            return [
                'id' => $annonce->id,
                'title' => $annonce->title,
                'description' => $annonce->description,
                'location' => $annonce->location,
                'sub_category_id' => $annonce->sub_category_id,
                'sous_category_id' => $annonce->sous_category_id,
                'image' => json_decode($annonce->image),
                'price' => $annonce->price,
                'type' => $annonce->type,
                'accepted_at' => $annonce->accepted_at,
                'sub_name' => $annonce->sub_Category->name,
            ];
        });
        return response()->json([
            'status' => 'success',
            'annonces' => $formattedAnnonces
        ]);
    }
    public function checkIsAbleToAddAnnonce()
    {
        $user = auth()->user();
        $annonces = $user->annonces()
            ->where('type', 'normal')
            ->orderBy('created_at', 'desc')
            ->get();
        $canAdd = false;

        if ($annonces->count() < 5) {
            $canAdd = true;
        } elseif ($annonces->count() == 5) {
            $lastAnnonce = $annonces->first();

            if ($lastAnnonce->created_at->lt(now()->subMonth())) {
                $canAdd = true;
            }
        }

        return response()->json([
            'status' => 'success',
            'canAdd' => $canAdd,
        ]);
    }


    public function createAnnonce(Request $request)
    {
        $user_id = Auth::guard('api')->user()->id;

        try {
            $request->validate([
                'title' => 'required|string',
                'description' => 'required|string',
                'location' => 'required|string',
                'sub_category_id' => 'required|integer',
                'sous_category_id' => 'nullable',
                'image' => 'nullable',
                'image.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'price' => 'required|integer',
                'annonce_type' => 'required|in:normal,vip',
            ]);

            $pictureUrls = [];

            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $file) {
                    if ($file->isValid()) {
                        $pictureName = 'img_' . time() . '_.' . $file->getClientOriginalExtension();

                        $file->storeAs('public/images', $pictureName);
                        $pictureUrls[] = 'storage/images/' . $pictureName;
                    } else {
                        return response()->json(['error' => 'File upload failed or invalid file.'], 400);
                    }
                }
            }

            $pictureUrlsJson = json_encode($pictureUrls);

            $annonce = Annonce::create([
                'title' => $request->title,
                'description' => $request->description,
                'location' => $request->location,
                'sub_category_id' => $request->sub_category_id,
                'sous_category_id' => $request->sous_category_id,
                'image' => $pictureUrlsJson,
                'user_id' => $user_id,
                'price' => $request->price,
                'type' => $request->annonce_type,
            ]);

            if ($request->annonce_type === 'vip') {
                Payment::create([
                    'annonce_id' => $annonce->id,
                    'status' => 'completed',
                    'payment_method' => $request->payment_method,
                    'annonce_duration' => $request->annonce_duration,
                    'amount' => $request->amount,
                ]);
            }

            return response()->json([
                "status" => "success",
                "message" => "Annonce created successfully",
                "annonce" => $annonce
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Annonce creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
