<?php namespace Blade\Orm\CodeGenerator;

class BaseGenerator
{
    private $templatesDir = [
        1 => __DIR__ . '/templates',
    ];


    /**
     * Установить директорию с кастомными шаблонами
     *
     * @param string $path
     */
    public function setTemplatesDir($path)
    {
        $this->templatesDir[0] = $path;
    }


    /**
     * Получить шаблон
     * Ищет в кастомной директории, потом в системной
     *
     * @param  string $fileName
     * @return bool|string
     */
    protected function getTemplate($fileName)
    {
        // системная директория должна быть последней
        ksort($this->templatesDir);
        foreach ($this->templatesDir as $dirPath) {
            $filePath = $dirPath . DIRECTORY_SEPARATOR . $fileName;
            if (is_file($filePath)) {
                return file_get_contents($filePath);
            }
        }
        throw new \RuntimeException(__METHOD__.": Template file `{$fileName}` not found in directories: ".implode('; ', $this->templatesDir));
    }
}
