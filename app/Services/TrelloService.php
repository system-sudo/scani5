<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TrelloService
{



    public function validateCredentials($key, $token)
    {
        // Make a request to Trello to verify the key and token
        $response = Http::get("https://api.trello.com/1/members/me", [
            'key' => $key,
            'token' => $token,
        ]);

        return $response->successful(); // Returns true if the response status is 200
    }


    public function getBoardIdAndListId($boardName, $listName, $key, $token)
    {

        // Validate credentials first
        if (!$this->validateCredentials($key, $token)) {
            return ['error' => 'Invalid Trello key or token.'];
        }

        // Get boards
        $boards = $this->getBoards($key, $token);

        // Find the board by name
        $board = collect($boards)->where('name', $boardName)->first();

        if (!$board) {
            return ['error' => 'Board not found.'];
        }

        // Get lists for the board
        $lists = $this->getLists($board['id'], $key, $token);

        // Find the list by name
        $list = collect($lists)->where('name', $listName)->first();

        if (!$list) {
            return ['error' => 'List not found.'];
        }

        return [
            'board_id' => $board['id'],
            'list_id' => $list['id'],
        ];
    }

    protected function getBoards($key, $token)
    {
        $response = Http::get("https://api.trello.com/1/members/me/boards", [
            'key' => $key,
            'token' => $token,
        ]);

        return $response->json();
    }

    protected function getLists($boardId, $key, $token)
    {
        $response = Http::get("https://api.trello.com/1/boards/{$boardId}/lists", [
            'key' => $key,
            'token' => $token,
        ]);

        return $response->json();
    }
}
