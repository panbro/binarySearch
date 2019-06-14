<?php

/**
 * осуществляет бинарный поиск значения по ключу в текстовом файле
 */
Class Search
{
    /** @var int константа, задающая размер записи  */
    public const FRAGMENT_SIZE = 4000000;

    /**
     * осуществляет бинарный поиск значения по ключу в текстовом файле
     * @param string $fileToOpen - файл, в котором требуется осуществить поиск
     * @param string $keyToSearch - ключ, который ищем
     * @return string
     */
    public function binarySearch(string $fileToOpen, string $keyToSearch): string
    {
        $file = fopen($fileToOpen, "r");//открываю файл (только для чтения)
        $fileSize = filesize($fileToOpen);//вычисляю размер файла для определения максимального значения - верхнего ограничения поиска
        $separatorLength = strlen('\\x0A');

        $comparison = false;//задаю основной параметр сравнения
        $start = 0;
        $end = $fileSize - 1;

        while ($comparison !== 0)
        {
            $middle = $start + ($end - $start) / 2;//нахожу середину файла
            $pointerToMiddle = fseek($file, $middle - $separatorLength);//ставлю указатель на середину файла
            $fragment = mb_convert_encoding(fgets($file, self::FRAGMENT_SIZE), 'UTF-8');//читаю строку из файла длиной максимального размера записи

            $startWithSlash = mb_strpos($fragment, 'x0A');//если фрагмент начинается на 'x0A' - php его распарсивает, и переменная $keyAndValueSeparatorPosition определяется не верно
            if ($startWithSlash == 0)//для того чтобы этого избежать, в таких случаях сдвигаю указатель на 1 символ влево, и перезаписываю строку $fragment
            {
                $pointerToMiddle = fseek($file, $middle - $separatorLength - 1);
                $fragment = fgets($file, self::FRAGMENT_SIZE);
            }

            $keyAndValueSeparatorPosition = mb_strpos($fragment, '\\x0A');//ищу позицию первого вхождение разделителя - подстроки '\x0A'
            $tabCharacterPosition = mb_strpos($fragment, '\\t');//ищу позицию первого вхождение табуляции - подстроки '\t'
            $keyLength = $tabCharacterPosition - $keyAndValueSeparatorPosition - $separatorLength;//вычисляю длину от '\x0A' до '\t' с учетом длины '\x0A'
            $key = mb_substr($fragment, ($keyAndValueSeparatorPosition + $separatorLength), $keyLength);//нахожу первый ключ для поиска
            $comparison = strnatcmp($keyToSearch, $key);//сравниваю этот ключ с тем, который задан в условии

//            echo "<pre>";
//            echo '$comparison = '. "$comparison"."<br/>".'$key = '."$key"."<br/>".'$fragment = '. "$fragment"."<br/><br/>";
//            echo "</pre>";

            if (!$comparison) { //если есть полное совпадение, то есть $comparison == 0, тогда вывожу искомое значение
                return $this->getValue($fragment, $tabCharacterPosition);
            }

            if ($key == "") { // если ключ для поиска задан больше максимального, ключ уходит в бесконетчность и отображает пустую строку.
                return "undef";
            }

            if ($comparison > 0) {//положительная переменная $comparison означает, что искать дальше нужно в бОльшей части файла
                $start = $middle;
            }

            if ($comparison < 0) {//отрицательная переменная $comparison означает, что искать дальше нужно в меньшей части файла
                $end = $middle;
            }
        }
    }

    /**
     * @param string $fragment - фрагмент, в котором найден искомый ключ
     * @param int $tabCharacterPosition - позиция '\t'
     * @return string
     */
    public function getValue(string $fragment, int $tabCharacterPosition): string
    {
        $tabulationLength = strlen('\\t');

        $startPositionOfValue = $tabCharacterPosition + $tabulationLength;//нахожу начальную и конечную позиции искомого значения
        $endPositionOfValue = mb_strpos($fragment,'\\x0A', $startPositionOfValue);
        $valueLength = $endPositionOfValue - $startPositionOfValue;//нахожу длину значения

        return mb_substr($fragment, $startPositionOfValue, $valueLength);//нахожу само значение
    }
}

/*Сюда можно указать путь до файла, вписать искомый ключ*/
$file = "./testFile";
$keyToSearch = "ключ7825";

$binarySearch = new Search;
print_r($binarySearch->binarySearch($file, $keyToSearch));