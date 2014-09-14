<?php namespace Barryvdh\TranslationManager;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Barryvdh\TranslationManager\Models\Translation;

class Controller extends BaseController
{
    /** @var \Barryvdh\TranslationManager\Manager  */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getIndex($group = null)
    {
        $locales = $this->loadLocales();
        $groups = Translation::groupBy('group');
        $excludedGroups = $this->manager->getConfig('exclude_groups');
        if($excludedGroups){
            $groups->whereNotIn('group', $excludedGroups);
        }

	    $paths = $this->makeShortPathsArray(Translation::select('path')->groupBy('path')->lists('path'));

        $groups = array(''=>'Choose a group') + $groups->lists('group', 'group');
        $numChanged = Translation::where('group', $group)->where('status', Translation::STATUS_CHANGED)->count();

        $allTranslations = Translation::where('group', $group)->orderBy('key', 'asc')->get();
        $numTranslations = count($allTranslations);
        $translations = array();
        foreach($allTranslations as $translation){
            $translations[$translation->path][$translation->key][$translation->locale] = $translation;
        }

        return \View::make('laravel-translation-manager::index')
            ->with('translations', $translations)
            ->with('locales', $locales)
            ->with('groups', $groups)
            ->with('group', $group)
            ->with('numTranslations', $numTranslations)
            ->with('numChanged', $numChanged)
            ->with('editUrl', URL::action(get_class($this).'@postEdit', array($group)))
            ->with('deleteEnabled', $this->manager->getConfig('delete_enabled'))
	        ->with('token', \Session::token())
	        ->with('paths', $paths)
            ;
    }

    protected function loadLocales()
    {
        //Set the default locale as the first one.
        $locales = array_merge(array(Config::get('app.locale')), Translation::groupBy('locale')->lists('locale'));
        return array_unique($locales);
    }

    public function postAdd($group)
    {
        $keys = explode("\n", Input::get('keys'));

        foreach($keys as $key){
            $key = trim($key);
            if($group && $key){
                $this->manager->missingKey('*', $group, $key);
            }
        }
        return Redirect::back();
    }

    public function postEdit($group)
    {
        if(!in_array($group, $this->manager->getConfig('exclude_groups'))) {
            $name = Input::get('name');
            $value = Input::get('value');

            list($locale, $key, $path) = explode('|', $name, 3);
            $translation = Translation::firstOrNew(array(
                'locale' => $locale,
                'group' => $group,
                'key' => $key,
                'path' => $path,
            ));
            $translation->value = (string) $value ?: null;
            $translation->status = Translation::STATUS_CHANGED;
            $translation->save();
            return array('status' => 'ok');
        }
    }

    public function postDelete($group, $key)
    {
        if(!in_array($group, $this->manager->getConfig('exclude_groups')) && !$this->manager->getConfig('delete_enabled')) {
            Translation::where('group', $group)->where('key', $key)->delete();
            return array('status' => 'ok');
        }
    }

    public function postImport()
    {
        $replace = Input::get('replace', false);
        $counter = $this->manager->importTranslations($replace);

        return Response::json(array('status' => 'ok', 'counter' => $counter));
    }
    
    public function postFind()
    {
        $numFound = $this->manager->findTranslations();

        return Response::json(array('status' => 'ok', 'counter' => (int) $numFound));
    }

    public function postPublish($group)
    {
        $this->manager->exportTranslations($group);

        return Response::json(array('status' => 'ok'));
    }

	public function getGroup($group)
	{
		return $this->getIndex($group);
	}

	private function makeShortPathsArray($paths)
	{
		if (count($paths) < 2)
		{
			$common = '';
		}
		else
		{
			$common = $paths[0];

			while ($common)
			{
				$equal = true;

				foreach($paths as $path)
				{
					if ( ! starts_with($path, $common))
					{
						$equal = false;

						break;
					}
				}

				if ($equal)
				{
					break;
				}

				$common = dirname($common);
			}
		}

		$result = array();

		foreach ($paths as $path)
		{
			$short = substr($path, strlen($common) + 1);

			$result[$path] = $short;
		}

		return $result;
	}

}
