<?php

namespace App;

use Model;
use Nette;

class SinglePresenter extends BasePresenter {

	/** @var \ISignInFormFactory @inject */
	public $signInFormFactory;
	/** @var \Model\Tags @inject */
	public $tags;

	public function renderObsah() {
		if (!$this->setting->show_content) {
			$this->error();
		}
		$articles = $this->posts->findBy(['publish_date <=' => new \DateTime()], ['title' => 'ASC']);
		$this->template->articles = $articles;

		$letters = [];
		foreach ($articles as $article) {
			$letter = mb_strtoupper(mb_substr($article->title, 0, 1, 'utf-8'));
			$letters[$letter] = $letter;
		}
		$this->template->letters = $letters;
	}

	public function renderArticle($slug) {
		$webalized = Nette\Utils\Strings::webalize($slug);
		if (empty($webalized)) {
			$this->redirect('Homepage:default');
		}
		if ($slug !== $webalized) {
			$this->redirect('Single:article', $webalized);
		}
		$post = $this->posts->findOneBy(['slug' => $webalized, 'publish_date <=' => new \DateTime()]); // zobrazení článku podle slugu
		$page = $this->pages->findOneBy(['slug' => $webalized]); // zobrazení stránky podle slugu
		if (!$post && !$page) { // pokud článek neexistuje (FALSE), pak forward - about, reference, atd...
			$this->forward($webalized);
		} elseif ($post) { // zobrazení klasických článků
			if (!$this->isAjax()) {
				$texy = $this->prepareTexy();
				$this->template->post = $post;
				$this->template->body = $texy->process($post->body);
			}
		} else { //PAGE
			$this->setView('page');
			$texy = $this->prepareTexy();
			$this->template->page = $page;
			$this->template->body = $texy->process($page->body);
		}
	}

	/** @return \Cntrl\SignIn */
	protected function createComponentSignInForm() {
		return $this->signInFormFactory->create();
	}

	/**
	 * @param $post_id
	 */
	public function handleGetNext($post_id) {
		if ($this->isAjax()) {
			$post = $this->posts->findOlder($post_id);
			if ($post) {
				$texy = $this->prepareTexy();
				$this->template->post = $post;
				$this->template->body = $texy->process($post->body);
				$this->redrawControl('article');
			}
		}
	}

}
