<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\Category;
use App\Models\Client;
use App\Models\Favoris;
use App\Models\Reclamation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{


    public function reclamation(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'client') {
            return response()->json(['error' => 'Only clients can create reclamations.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $reclamation = Reclamation::create([
            'message' => $request->message,
            'user_id' => $user->id,
        ]);

        return response()->json([
            "status" => "success",
            "message" => "Reclamation created successfully",
            "reclamation" => $reclamation
        ], 201);
    }

    public function favoris(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'client') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'annonce_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $favoris = Favoris::where('user_id', $user->id)
            ->where('annonce_id', $request->annonce_id)
            ->first();

        if ($favoris) {
            $favoris->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'favoris removed successfully',
                'favoris' => null
            ], 200);
        } else {
            $favoris = Favoris::create([
                'annonce_id' => $request->annonce_id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'favoris set successfully',
                'favoris' => $favoris
            ], 200);
        }
    }

    public function checkFavoris(Request $request)
    {
        $user = Auth::user();
        $annonceId = $request->input('annonce_id');

        $favoris = Favoris::where('user_id', $user->id)
            ->where('annonce_id', $annonceId)
            ->exists();

        return response()->json(['favorited' => $favoris]);
    }

    public function getAnnonces()
    {
        try {
            $userId = auth()->id();
            $annonces = Annonce::with(['user', 'sub_Category'])->paginate(6);

            // Check if the user has favorited any annonces
            $favoritedAnnonceIds = $userId ? Favoris::where('user_id', $userId)->pluck('annonce_id')->toArray() : [];

            $formattedAnnonces = $annonces->getCollection()->map(function ($annonce) use ($favoritedAnnonceIds) {
                return [
                    'id' => $annonce->id,
                    'title' => $annonce->title,
                    'description' => $annonce->description,
                    'location' => $annonce->location,
                    'sub_category_id' => $annonce->sub_category_id,
                    'sous_category_id' => $annonce->sous_category_id,
                    'images' => json_decode($annonce->image),
                    'price' => $annonce->price,
                    'type' => $annonce->type,
                    'sub_name' => $annonce->sub_Category->name,
                    'firstName' => $annonce->user->firstName,
                    'lastName' => $annonce->user->lastName,
                    'phone' => $annonce->user->phone,
                    'created_at' => $annonce->created_at,
                    'isFavorited' => in_array($annonce->id, $favoritedAnnonceIds), // Check if this annonce is favorited
                ];
            });

            return response()->json([
                'data' => $formattedAnnonces,
                'current_page' => $annonces->currentPage(),
                'last_page' => $annonces->lastPage(),
                'per_page' => $annonces->perPage(),
                'total' => $annonces->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch annonces', 'message' => $e->getMessage()], 500);
        }
    }

    public function getAllAcceptedAnnoncesOLD(Request $request)
    {
        try {
            $userId = auth()->id();
            $type = $request->type;

            $annonces = Annonce::whereNotNull('accepted_at')
                ->when($type, function ($query) use ($type) {
                    return $query->where('type', $type);
                })
                ->with(['user', 'sub_Category'])
                ->paginate(6);

            // Check if the user has favorited any annonces
            $favoritedAnnonceIds = $userId ? Favoris::where('user_id', $userId)->pluck('annonce_id')->toArray() : [];

            $formattedAnnonces = $annonces->getCollection()->map(function ($annonce) use ($favoritedAnnonceIds) {
                return [
                    'id' => $annonce->id,
                    'title' => $annonce->title,
                    'description' => $annonce->description,
                    'location' => $annonce->location,
                    'sub_category_id' => $annonce->sub_category_id,
                    'sous_category_id' => $annonce->sous_category_id,
                    'images' => json_decode($annonce->image),
                    'price' => $annonce->price,
                    'type' => $annonce->type,
                    'sub_name' => $annonce->sub_Category->name,
                    'firstName' => $annonce->user->firstName,
                    'lastName' => $annonce->user->lastName,
                    'phone' => $annonce->user->phone,
                    'created_at' => $annonce->created_at,
                    'isFavorited' => in_array($annonce->id, $favoritedAnnonceIds), // Check if this annonce is favorited
                ];
            });

            return response()->json([
                'data' => $formattedAnnonces,
                'current_page' => $annonces->currentPage(),
                'last_page' => $annonces->lastPage(),
                'per_page' => $annonces->perPage(),
                'total' => $annonces->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch annonces', 'message' => $e->getMessage()], 500);
        }
    }
    public function getAllAcceptedAnnonces()
    {
        try {
            $userId = auth()->id();

            $annoncesMarriage = Annonce::query()
                ->select('annonces.*', 'sub_categories.name as sub_category_name', 'categories.name as category_name')
                ->join('sub_categories', 'annonces.sub_category_id', '=', 'sub_categories.id')
                ->join('categories', 'sub_categories.category_id', '=', 'categories.id')
                ->where('categories.name', 'marriage')
                ->whereNotNull('annonces.accepted_at')
                ->where('annonces.type', 'normal')->paginate(6);

            $annoncesBabyshower = Annonce::query()
                ->select('annonces.*', 'sub_categories.name as sub_category_name', 'categories.name as category_name')
                ->join('sub_categories', 'annonces.sub_category_id', '=', 'sub_categories.id')
                ->join('categories', 'sub_categories.category_id', '=', 'categories.id')
                ->where('categories.name', 'babyshower')
                ->whereNotNull('annonces.accepted_at')
                ->where('annonces.type', 'normal')->paginate(6);

            $annoncesAnniversaire = Annonce::query()
                ->select('annonces.*', 'sub_categories.name as sub_category_name', 'categories.name as category_name')
                ->join('sub_categories', 'annonces.sub_category_id', '=', 'sub_categories.id')
                ->join('categories', 'sub_categories.category_id', '=', 'categories.id')
                ->where('categories.name', 'anniversaire')
                ->whereNotNull('annonces.accepted_at')
                ->where('annonces.type', 'normal')->paginate(6);

            $annoncesNormal = Annonce::whereNotNull('accepted_at')
                ->where('type', 'normal')
                ->with(['user', 'sub_Category'])
                ->paginate(6);

            $annoncesVip = Annonce::whereNotNull('accepted_at')
                ->where('type', 'vip')
                ->with(['user', 'sub_Category'])
                ->paginate(6);

            $favoritedAnnonceIds = $userId ? Favoris::where('user_id', $userId)->pluck('annonce_id')->toArray() : [];

            $allFormatedAnnoncesNormal = $this->formatAnnonces($annoncesNormal, $favoritedAnnonceIds);
            $allFormatedAnnoncesVIP = $this->formatAnnonces($annoncesVip, $favoritedAnnonceIds);
            $allFormatedAnnoncesMarriage = $this->formatAnnonces($annoncesMarriage, $favoritedAnnonceIds);
            $allFormatedAnnoncesBabyshower = $this->formatAnnonces($annoncesBabyshower, $favoritedAnnonceIds);
            $allFormatedAnnoncesAnniversaire = $this->formatAnnonces($annoncesAnniversaire, $favoritedAnnonceIds);
            
            return response()->json([
                'normal' => $allFormatedAnnoncesNormal,
                'vip' => $allFormatedAnnoncesVIP,
                'marriage' => $allFormatedAnnoncesMarriage,
                'babyshower' => $allFormatedAnnoncesBabyshower,
                'anniversaire' => $allFormatedAnnoncesAnniversaire,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch annonces', 'message' => $e->getMessage()], 500);
        }
    }

    private function formatAnnonces($annonces, $favoritedAnnonceIds)
    {
        return $annonces->getCollection()->map(function ($annonce) use ($favoritedAnnonceIds) {
            return [
                'id' => $annonce->id,
                'title' => $annonce->title,
                'description' => $annonce->description,
                'location' => $annonce->location,
                'sub_category_id' => $annonce->sub_category_id,
                'sous_category_id' => $annonce->sous_category_id,
                'images' => json_decode($annonce->image),
                'price' => $annonce->price,
                'type' => $annonce->type,
                'sub_name' => $annonce->sub_Category->name,
                'firstName' => $annonce->user->firstName,
                'lastName' => $annonce->user->lastName,
                'phone' => $annonce->user->phone,
                'created_at' => $annonce->created_at,
                'isFavorited' => in_array($annonce->id, $favoritedAnnonceIds),
            ];
        });
    }

    public function getAllDetails()
    {
        $annonces = Annonce::with(['user', 'sub_Category', 'sous_Category'])->get();
        return response()->json($annonces);
    }

    public function getAnnonceDetails($id)
    {
        try {
            $annonce = Annonce::with(['user', 'sub_Category'])->findOrFail($id);

            $formattedAnnonce = [
                'id' => $annonce->id,
                'user_id' => $annonce->user_id,
                'title' => $annonce->title,
                'description' => $annonce->description,
                'location' => $annonce->location,
                'sub_category_id' => $annonce->sub_category_id,
                'sous_category_id' => $annonce->sous_category_id,
                'image' => json_decode($annonce->image),
                'price' => $annonce->price,
                'sub_name' => $annonce->sub_Category->name,
                'firstName' => $annonce->user->firstName,
                'lastName' => $annonce->user->lastName,
                'phone' => $annonce->user->phone,
                'created_at' => $annonce->created_at,
            ];

            return response()->json([
                'status' => 'success',
                'annonce' => $formattedAnnonce
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching announcement details.'], 500);
        }
    }

    public function filterAnnonces(Request $request)
    {
        try {
            $userId = auth()->id();

            $category = $request->query('category');
            $subCategory = $request->query('subCategory');
            $sousCategory = $request->query('sousCategory');
            $city = $request->query('city');
            $search = $request->query('search');

            $query = Annonce::query()
                ->select('annonces.*', 'sub_categories.name as sub_category_name', 'categories.name as category_name')
                ->join('sub_categories', 'annonces.sub_category_id', '=', 'sub_categories.id')
                ->join('categories', 'sub_categories.category_id', '=', 'categories.id');

            if ($category) {
                $query->where('categories.name', $category);
            }
            if ($subCategory) {
                $query->where('sub_categories.name', $subCategory);
            }
            if ($sousCategory) {
                $query->where('sous_categories.name', $sousCategory);
            }
            if ($city) {
                $query->where('location', 'like', '%' . $city . '%');
            }

            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%');
                });
            }

            $favoritedAnnonceIds = $userId ? Favoris::where('user_id', $userId)->pluck('annonce_id')->toArray() : [];

            $annonces = $query->get();

            $formattedAnnonces = $annonces->map(function ($annonce) use ($favoritedAnnonceIds) {
                return [
                    'id' => $annonce->id,
                    'title' => $annonce->title,
                    'description' => $annonce->description,
                    'price' => $annonce->price,
                    'type' => $annonce->type,
                    'location' => $annonce->location,
                    'sub_category' => $annonce->sub_category->name,
                    'isFavorited' => in_array($annonce->id, $favoritedAnnonceIds),
                    'images' => json_decode($annonce->image),
                ];
            });

            return response()->json([
                'status' => 'success',
                'annonces' => $formattedAnnonces,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching annonces.'], 500);
        }
    }

    public function getAllCategories()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function getCategoriesWithAnnonces()
    {
        $categories = Category::with(['Sub_Category' => function ($query) {
            $query->withCount(['annonces' => function ($query) {
                $query->whereNotNull('accepted_at');
            }]);
        }])
            ->withCount(['annonces' => function ($query) {
                $query->whereNotNull('accepted_at');
            }])
            ->get();


        return response()->json($categories);
    }
}
