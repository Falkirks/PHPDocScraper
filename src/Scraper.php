<?php
namespace phpdoc;

use CanGelis\PDF\PDF;
use League\Flysystem\Adapter\Local;

class Scraper{
    private $progressBar;

    private $urlQueue;
    private $followRegex;
    private $storeRegex;
    private $siteUrl;
    private $pathUrl;

    private $doneQueue;

    public function __construct($baseUrl, $followRegex, $storeRegex){
        getCLI()->br(2);
        $this->progressBar = getCLI()->progress();
        $this->pathUrl = substr($baseUrl, 0, strrpos($baseUrl, "/")+1);
        $part = substr($this->pathUrl, strpos($this->pathUrl, "."));
        $this->siteUrl = substr($this->pathUrl, 0, strpos($part, "/")+(strlen($this->pathUrl)-strlen($part)));
        $this->urlQueue = [$baseUrl];
        $this->followRegex = $followRegex;
        $this->storeRegex = $storeRegex;
    }
    public function run(){
        while(!empty($this->urlQueue)){
            $this->progressBar->total(count($this->urlQueue));
            $url = array_shift($this->urlQueue);
            //getCLI()->out($url);
            $html = @file_get_contents($url);
            $links = $this->findLinks($html);
            if(!empty($links)) {
                array_push($this->urlQueue, ...$links);
            }

            if(preg_match($this->storeRegex, $url) === 1){
                //getCLI()->out($url);
                $name = substr($html, strpos($html, "<h1 class=\"refname\">")+20);
                $name = substr($name, 0, strpos($name, "</h1>"));
                $html = preg_replace("~<nav (.*)</nav>~", "", $html);
                $html = preg_replace("~<div id=\"breadcrumbs\" (.*)</div>~", "", $html);
                $html = preg_replace("~<aside class=\"layout-menu\" (.*)</aside>~", "", $html);
                file_put_contents(MAIN_PATH . "/out/pages/$name.html", $html);
                //print $html;
                exec("wkhtmltopdf " . MAIN_PATH . "/out/pages/$name.html " . MAIN_PATH . "/out/pages/$name.pdf");
            }
            $this->doneQueue[$url] = $url;
            $this->progressBar->current(count($this->doneQueue), (isset($name) ? $name : $url));
        }
        getCLI()->blink("YAY! All function documentation has been scraped.");
    }
    public function findLinks($html){
        preg_match_all("/href=\"([^\"]*\")/", $html, $matches);
        $out = [];
        foreach($matches[1] as $match){
            $match = substr($match, 0, -1);
            if(empty($match)) continue;
            if($match{0} === "/"){
                $match = $this->siteUrl . $match;
            }
            elseif(strpos($match, "http://") === 0 || strpos($match, "https://") === 0){
                //
            }
            elseif(strpos($match, "javascript:") === 0){
                continue;
            }
            else{
                $match = $this->pathUrl . $match;
            }
            if(!isset($this->doneQueue[$match]) && (preg_match($this->followRegex, $match) === 1 || preg_match($this->storeRegex, $match) === 1)) {
                //getCLI()->out($match);
                $out[] = $match;
            }
        }
        return $out;
    }
}