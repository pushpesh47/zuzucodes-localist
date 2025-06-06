<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\Category;
use App\Models\Menu;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CustomHelper;

class MenuController extends Controller
{
    public function index()
    {
        $aRows = Menu::with(['parent','pages'])->orderBy('id','DESC')->get(); 
        return view('menus.index', compact('aRows'));
    }

    public function create()
    {
        $aRow = array();
        $parents = Menu::orderBy('id','DESC')->get();
        $pagemenu = Page::where('status',1)->get();
        return view('menus.create',get_defined_vars());
    }

    public function store(Request $request)
    {
        $this->validateSave($request);   
        return redirect()->route('menus.index')->with('success', 'Menu created successfully.');
    }

    public function show(Menu $menu)
    {
        return $menu;
    }

    public function edit(Menu $menu)
    {
        
        $aRow = $menu;
        $parents = Menu::where('menu_name','!=',$aRow->menu_name)->orderBy('id','DESC')->get();
        $pagemenu = Page::where('status',1)->get();
        return view('menus.create',get_defined_vars());
    }

    public function update(Request $request, Menu $menu)
    {
        $this->validateSave($request,$menu);      
        return redirect()->route('menus.index')
                         ->with('success', 'Menu updated successfully.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('menus.index')
                         ->with('success', 'Menu deleted successfully.');
    }

    protected function validateSave(Request $request,$isEdit = "")
    {
        $aValids['menu_name'] =  'required';
        if ($isEdit) {
            if($isEdit->menu_pageid !=  $request->menu_pageid){
                $aValids['menu_pageid'] = 'required|unique:menus,menu_pageid,' . $isEdit->id;
            }else{
                $aValids['menu_pageid'] = 'required';
            }
            
        } else {
            $aValids['menu_pageid'] = 'required|unique:menus,menu_pageid';
        }
        $request->merge(['menu_parent' => $request->menu_parent ?? 0]);
        
        $validated = $request->validate($aValids);
        $aVals = $request->all();
        if($isEdit)
        {
            $isEdit->update($aVals);
        }
        else{
            $page  = Menu::create($aVals);
        }
    }
    
}

