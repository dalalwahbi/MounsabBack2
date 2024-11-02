<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\Client;
use App\Models\Prestataire;
use App\Models\Reclamation;
use App\Models\User;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function getAllPrestataires()
    {
        $prestataires = Prestataire::with('user')->get();
        return response()->json($prestataires);
    }
    public function getAllClients()
    {
        $clients = Client::with('user')->get();
        return response()->json($clients);
    }

    public function getAllReclamations()
{
    $reclamations = Reclamation::with('user')->paginate(6);
    return response()->json($reclamations);
}
public function getAllAnnonces()
{
    $annonces = Annonce::whereNull('accepted_at')
        ->with(['user', 'sub_Category']) 
        ->paginate(4);

    $formattedAnnonces = $annonces->getCollection()->map(function ($annonce) {
        return [
            'user' => $annonce->user,
            'id' => $annonce->id,
            'title' => $annonce->title,
            'description' => $annonce->description,
            'location' => $annonce->location,
            'sub_category_id' => $annonce->sub_category_id,
            'sous_category_id' => $annonce->sous_category_id,
            'image' => json_decode($annonce->image),
            'price' => $annonce->price,
            'accepted_at' => $annonce->accepted_at,
            'sub_name' => $annonce->sub_Category->name, 
        ];
    });

    // Return both the formatted annonces and pagination meta data
    return response()->json([
        'data' => $formattedAnnonces,
        'current_page' => $annonces->currentPage(),
        'last_page' => $annonces->lastPage(),
        'total' => $annonces->total(),
    ]);
}

    public function banUsers()
    {
        $now = Carbon::now();

        User::whereNull('banned_at')->update(['banned_at' => $now]);

        return response()->json(['message' => 'Users have been banned successfully.']);
    }

    public function getLatestPrestataires()
    {
        $latestPrestataires = Prestataire::with('user')->orderBy('created_at', 'desc')->take(5)->get();
        return response()->json($latestPrestataires);
    }
    public function getLatestClients()
    {
        $LatestClients = Prestataire::with('user')->orderBy('created_at', 'desc')->take(5)->get();
        return response()->json($LatestClients);
    }

    public function getLatestAnnonces()
    {
        $LatestAnnonces = Annonce::with('user')->orderBy('created_at', 'desc')->take(5)->get();
        return response()->json($LatestAnnonces);
    }

    public function getLatestReclamations()
    {
        $LatestReclamation = Reclamation::with('user')->orderBy('created_at', 'desc')->take(5)->get();
        return response()->json($LatestReclamation);
    }

    public function countPrestataires()
    {
        try {
            $count = Prestataire::count(); 
            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to count prestataires'], 500);
        }
    }

    public function countClients()
    {
        try {
            $count = Client::count(); 
            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to count Clients'], 500);
        }
    }

    public function countAnnonces()
    {
        try {
            $count = Annonce::count(); 
            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to count annonces'], 500);
        }
    }

    public function countReclamations()
    {
        try {
            $count = Reclamation::count(); 
            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to count reclamation'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $annonce = Annonce::findOrFail($id);
            $annonce->delete();

            return response()->json(['message' => 'Annonce deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while deleting the annonce'], 500);
        }
    }

    public function destroyReclamations($id)
    {
        try {
            $reclamation = Reclamation::findOrFail($id);
            $reclamation->delete();

            return response()->json(['message' => 'Reclamation deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while deleting the Reclamation'], 500);
        }
    }

    public function acceptAnnonce($id)
    {
        try {
            $annonce = Annonce::findOrFail($id);
            
            if ($annonce->accepted_at !== null) {
                return response()->json(['message' => 'Annonce already accepted'], 400);
            }
            
            $annonce->accepted_at = now();
            $annonce->save();

            return response()->json(['message' => 'Annonce accepted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to accept annonce', 'error' => $e->getMessage()], 500);
        }
    }
}
