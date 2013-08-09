<?php

namespace Flurry\Middleware;

class FieldSelectors extends \Slim\Middleware {

	public function parseURI($uri) {
		if(preg_match('/~:\((.*)\)/', $uri, $matches)) {
			$fields = explode(',', $matches[1]);
			$uri = preg_replace('/~:\(.*\)/', '', $uri);
			return array($fields, $uri);
		} else {
			return array(array(), $uri);
		}
	}

	public function call() {
		$uri = $this->app->environment()['PATH_INFO'];
		list($fieldSelectors, $newURI) = $this->parseURI($uri);
		if(!empty($fieldSelectors)) {
			// put selectors in request
			$this->app->request->fieldSelectors = $fieldSelectors;
			$this->app->environment()['PATH_INFO'] = $newURI;
		}
		$this->next->call();
	}
}
