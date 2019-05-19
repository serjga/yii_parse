<?php
namespace common\models;

use Yii;
use yii\base\Model;
use yii\base\Component;
use yii\httpclient\Client;
use yii\httpclient\Request;
use yii\httpclient\Response;
use Exception;
use keltstr\simplehtmldom\SimpleHTMLDom;

/**
 * Login form
 */
class Robot extends Component
{
    const DEFAULT_URL = "https://dabi.gov.ua/declarate/list.php?sort=num&order=DESC";

    public $lastResponse;
    public $lastRequest;
    public $client;

    public $years = ['2010', '2011', '2012', '2013', '2014', '2015', '2016', '2017', '2018', '2019'];
    public $monthes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
    public $searchWords = ['Харківський р-н', 'Люботин'];

    public $year;
    public $month;
    public $keyWord;


    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        parent::init();
        $this->client = $client = new Client([
            "transport" => "yii\httpclient\CurlTransport",
            "baseUrl" => self::DEFAULT_URL,
        ]);
    }

    public function getPage($url = self::DEFAULT_URL)
    {
        try{
            echo $url . PHP_EOL;
            $request = $this->client->get($url);
            $this->lastResponse = $request->send();

            $year = $this->year;
            $month = $this->month;
            $word = $this->keyWord;

            echo $year . PHP_EOL;
            echo $month . PHP_EOL;
            echo $word . PHP_EOL;

        if(stristr($url, "page") === FALSE) {
            $data = [
                'filter[regob]' => 20,
                'filter[date]' => $year,
                'filter[date2]' => $month,
                'filter[subgaluz]' => '',
                'filter[galuz]' => '',
                'filter[class]' => '',
                'filter[confind]' => $word
            ];

            $request = $this->client->post($url, $data, [
                'X-Requested-With' => 'POST'
            ]);
            $this->lastResponse = $request->send();
        }
            // var_dump($this->lastResponse->content);
            $page = SimpleHTMLDom::str_get_html($this->lastResponse->content);

            foreach($page->find("tr") as $tr) {

               if(stristr($tr, "class=\"pages\"") === FALSE
                && stristr($tr, "class=\"header\"") === FALSE
                && stristr($tr, "id=\"tableHead\"") === FALSE
                && stristr($tr, "(індивідуальних)") === FALSE
                && stristr($tr, "(садибний)") === FALSE
                && stristr($tr, "веранда") === FALSE
                && stristr($tr, "Індивідуальний") === FALSE
                && stristr($tr, "індивідуального") === FALSE
                && stristr($tr, "Садовий будинок") === FALSE
                && stristr($tr, "садового будинку") === FALSE
                && stristr($tr, "тренажерного майданчику") === FALSE
                && stristr($tr, "Будівництво житлового будинку") === FALSE
                && stristr($tr, "Будівництво садового будинку") === FALSE
                && stristr($tr, "з господарськими будівлями") === FALSE
                && stristr($tr, "господарські будівлі") === FALSE
                && stristr($tr, "господарська споруда") === FALSE
                && stristr($tr, "господарських будівель") === FALSE
                && stristr($tr, "господарських споруд") === FALSE
                && stristr($tr, "Господарський спосіб будівництва") === FALSE
                && stristr($tr, "Реконструкція житлового будинку") === FALSE) {
                   //  echo $tr . PHP_EOL;
                    $content = file_get_contents('parse/content.html');
                    $content .= '<tr><td colspan=11><b>год:</b> '. $year .'; <b>месяц:</b> '. $month .'</td></tr>' . $tr;
                    // Пишем содержимое обратно в файл
                    file_put_contents('parse/content.html', $content);
                    // *********************************************************
                    $td = $tr->find("td");
                    echo $td[0]->plaintext;

                        $find = file_get_contents('parse/parse.txt');

                        $find .= 'Год: '.$year.';^р';
                        $find .= 'Месяц: '.$year.';^р';
                        $find .= '№: '.str_replace("&nbsp;", "", $td[0]->plaintext) .';^р';
                        $find .= 'Область: '.str_replace("&nbsp;", "", $td[1]->plaintext).';^р';
                        $find .= 'Документ: '.str_replace("&nbsp;", "", $td[2]->plaintext).';^р';
                        $find .= 'Об`єкт: '.str_replace("&nbsp;", "", $td[3]->plaintext).';^р';
                        $find .= 'Кат.: '.str_replace("&nbsp;", "", $td[4]->plaintext).';^р';
                        $find .= 'Замовник: '.str_replace("&nbsp;", "", $td[5]->plaintext).';^р';
                        $find .= 'Технічний нагляд: '.str_replace("&nbsp;", "", $td[6]->plaintext).';^р';
                        $find .= 'Проектувальник: '.str_replace("&nbsp;", "", $td[7]->plaintext).';^р';
                        $find .= 'Авторський нагляд: '.str_replace("&nbsp;", "", $td[8]->plaintext).';^р';
                        $find .= 'Підрядник: '.str_replace("&nbsp;", "", $td[9]->plaintext).';^р';
                        $find .= 'Інформація про земельну ділянку: '.str_replace("&nbsp;", "", $td[10]->plaintext).';^р';
                        $find .= '\n';

                        // Пишем содержимое обратно в файл
                        file_put_contents('parse/parse.txt', $find);
  
                }
            }            

            $num = 0;
            foreach($page->find("#pages a") as $a) {
                $num = $a->href;
            }

            if($num != '0') {

                $num = str_replace("?&&page=", "", $num);
                echo $num;

                for($i = 2; $i <= +$num; $i++) {
                    $this->getPage("https://dabi.gov.ua/declarate/list.php?sort=num&order=DESC&page=" . $i);
                }
            }
        }
        catch(Exception $e) {
            echo $e->getMessage() . ' ' . $e->getCode() . ' ' . $e->getLine() . ' ' . $e->getFile();
        }
    }

    public function searchAll()
    {
        file_put_contents('parse/content.html', '');
        file_put_contents('parse/parse.txt', '');
        foreach($this->years as $year) {
            $this->year = $year;

            foreach($this->monthes as $month) {
                $this->month = $month;

                foreach($this->searchWords as $word) {
                    $this->keyWord = $word;
                    $this->getPage();
                } 
            }    
        }

        $table = file_get_contents('parse/content.html');
        $table = "<table border='1' width='100%'>" . $table . '</table>';
        file_put_contents('parse/content.html', $table);
    }
}