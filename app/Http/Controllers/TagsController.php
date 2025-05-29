<?php

namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use App\Http\Resources\CommonResource;
use App\Models\Asset;
use App\Models\Vulnerability;
use App\ResponseApi;
use App\Traits\TaggableTrait;
use Illuminate\Http\Request;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TagsController extends Controller
{
    use ResponseApi;
    use TaggableTrait;

    /**
     * Tag page - index
     */
    public function index(Request $request)
    {
        // $arr = [0, 0, 0, 0, 0, 0, 0, 7, 0];

        // $j = 0;

        // for ($i = 0; $i < count($arr); $i++) {
        //     if ($arr[$i] != 0) {
        //         if ($i != $j) {
        //             $tmp = $arr[$i];
        //             $arr[$i] = $arr[$j];
        //             $arr[$j] = $tmp;
        //         }
        //         $j++;
        //     }
        // }

        // return $arr;

        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $search = request('search');
        $sort_column = request('sort_column');
        $sort_direction = request('sort_direction');
        $accepted_sort_columns = ['name', 'created_at'];

        try {
            $tags = Tag::select('id', 'name', 'created_at', 'updated_at')->search($search, ['name'])
                ->where('organization_id', $orgId)
                ->sort($sort_column, $sort_direction, $accepted_sort_columns)
                ->orderByDesc('created_at')
                ->paginateresults();

            $tagRecords = CommonResource::collection($tags)->response()->getData(true);

            return $this->sendResponse($tagRecords, 'Tags displayed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    public function count(Request $request)
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $tags_count = Tag::where('organization_id', $orgId)->count();

        $response = [
            'total' => $tags_count,
        ];

        return $this->sendResponse($response, 'Tag cards displayed successfully');
    }

    public function reportTags(Request $request)
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        $type = request('type');

        try {
            $tags = Tag::select('id', 'name')->where('organization_id', $orgId)
            ->when($type, function ($query) use ($type) {
                $query->whereHas($type);
            })->get();

            return $this->sendResponse($tags, 'Tags displayed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Add tag
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orgId' => 'required|exists:organizations,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags')->where(function ($query) use ($request) {
                    return $query->where('organization_id', $request->input('orgId'));
                }),
            ],
        ], [
            'orgId.required' => 'Organization is required.',
            'orgId.exists' => 'Organization does not exist.',
            'name.required' => 'Name is required.',
            'name.string' => 'Name must be a valid string.',
            'name.max' => 'Name must not be greater than 255 characters',
            'name.unique' => 'This tag name is already used in this organization.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if (!postIsallowed($request->orgId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        try {
            Tag::Create(['name' => $request->name, 'organization_id' => $request->orgId]);

            LogHelper::logAction('Created', 'Tag', "User added the tag '{$request->name}'", getRoleId(), $request->orgId);

            return $this->sendResponse(null, 'Tag added successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Update tag
     */
    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orgId' => 'required|exists:organizations,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags')->where(function ($query) use ($request) {
                    return $query->where('organization_id', $request->input('orgId'));
                }),
            ],
        ], [
            'orgId.required' => 'Organization is required.',
            'orgId.exists' => 'Organization does not exist.',
            'name.required' => 'Name is required.',
            'name.string' => 'Name must be a valid string.',
            'name.max' => 'Name must not be greater than 255 characters',
            'name.unique' => 'This tag name is already used in this organization.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if (!postIsallowed($request->orgId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        try {
            $tag = Tag::find($id);
            if ($tag) {
                $tag->name = $request->name;
                $tag->save();
            }

            LogHelper::logAction('Updated', 'Tag', "User updated the tag '{$request->name}'", getRoleId(), $request->orgId);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }

        return $this->sendResponse(null, 'Tag updated successfully');
    }

    /**
     * Assign tag
     */
    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:asset,vulnerability',
            'assetId' => 'required|integer',
            'orgId' => 'required|integer',
            'is_retired' => 'required|in:0,1',
        ], [
            'orgId.required' => 'Organization is required.',
            'orgId.integer' => 'Organization must be an integer.',
            'assetId.required' => 'Asset is required.',
            'assetId.integer' => 'Asset must be an integer.',
            'type.required' => 'AssetType is required.',
            'type.string' => 'AssetType must be a valid string.',
            'type.in' => 'AssetType is invalid',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if (!postIsallowed($request->orgId)) {
            return $this->sendError("Your role doesn't have permission to access this request", 403);
        }

        $is_retired = request('is_retired');

        try {
            $modelClass = strtolower($request->type) === 'asset' ? Asset::class : Vulnerability::class;
            DB::table('taggables')
                ->where('taggable_type', $modelClass)
                ->where('taggable_id', $request->assetId)->delete();
            $modelInstance = $modelClass::whereId($request->assetId)
            ->when($is_retired, function ($query) {
                return $query->onlyTrashed();
            })
            ->first();
            if (!$modelInstance) {
                return $this->sendError("{$request->type} not found.", null, 500);
            }

            $log_details = '';

            if (count($request->tagId) > 0) {
                $modelInstance->tags()->attach($request->tagId);

                if ($request->type == 'asset') {
                    $log_details = "User assigned tags for this IP : '{$modelInstance->ip_address_v4}'";
                } else {
                    $log_details = "User assigned tags for this Vulnerability : '{$modelInstance->name}'";
                }

                LogHelper::logAction('Added', 'Tag', $log_details, getRoleId(), $request->orgId);

                return $this->sendResponse(null, 'Tag assigned successfully');
            } else {
                return $this->sendResponse(null, 'Tag removed successfully');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Remove tag
     */
    public function remove($orgId, $module_id, $type)
    {
        try {
            $modelClass = strtolower($type) === 'asset' ? Asset::class : Vulnerability::class;
            DB::table('taggables')
            ->where('taggable_type', $modelClass)
            ->where('taggable_id', $module_id)->delete();

            $modelInstance = $modelClass::find($module_id);

            $log_details = '';

            if ($type == 'asset') {
                $log_details = "User assigned tags for this IP : '{$modelInstance->ip_address_v4}'";
            } else {
                $log_details = "User assigned tags for this Vulnerability : '{$modelInstance->name}'";
            }

            LogHelper::logAction('Removed', 'Tag', $log_details, getRoleId(), $orgId);

            return $this->sendResponse(null, 'Tag removed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Delete tag
     */
    public function destroy($orgId, $id, Request $request)
    {
        try {
            $tag = Tag::whereId($id)->where('organization_id', $orgId)->first();
            if (!$tag) {
                return $this->sendError('Tag not found', null, 404);
            }

            if ($tag) {
                $tag->delete();
            }

            LogHelper::logAction('Deleted', 'Tag', "User deleted the tag '{$tag->name}'", getRoleId(), $request->orgId);

            return $this->sendResponse(null, 'Tag deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }

    /**
     * Delete asset tag
     */
    // public function deleteAssetTag(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'type' => 'required|string|in:asset,vulnerability',
    //         'id' => 'required|integer',
    //         'orgId' => 'required|integer',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors());
    //     }

    //     if (!postIsallowed($request->orgId)) {
    //         return $this->sendError("Your role doesn't have permission to access this request", 403);
    //     }
    //     try {
    //         $modelInstance = strtolower($request->type) === 'asset' ? Asset::class : Vulnerability::class;
    //         $records = $modelInstance::find($request->id);
    //         if ($request->type == 'asset') {
    //             $log_details = "Deleted tags for this IP : '{$records->ip_address_v4}'";
    //         } else {
    //             $log_details = "Deleted tags for this Vulnerability : '{$records->name}'";
    //         }

    //         DB::table('taggables')->where('taggable_type', $modelInstance)->where('taggable_id', $request->assetId)->delete();
    //         LogHelper::logAction('Deleted', 'Tag', "User cleared all tags for this IP address : '{$log_details}'", getRoleId(), $request->orgId);
    //         return $this->sendResponse(null, 'Tags cleared successfully');
    //     } catch (\Exception $e) {
    //         // Send error response
    //         return $this->sendError('Failed to clear tags. Please try again later.');
    //     }
    // }

    /**
     * Clear all tags
     */
    public function destroyAll(Request $request)
    {
        $orgId = request('orgId');
        if (!$orgId) {
            return $this->sendError('Organization is mandatory');
        }

        try {
            Tag::where('organization_id', $orgId)->delete();

            LogHelper::logAction('Created', 'Tag', 'User cleared all tags', getRoleId(), $orgId);

            return $this->sendResponse(null, 'All tags cleared successfully');
        } catch (\Exception $e) {
            // Send error response
            return $this->sendError('Failed to clear tags. Please try again later.');
        }
    }
}
