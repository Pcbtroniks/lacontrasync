<?php

class PrintData {

    public $_data;

    /**
     * @param $data array
     */
    public function __construct($data)
    {
        $this->_data = $data;
    }

    public function asTable()
    {
        $table = '<table border="1">';
        $table .= '<thead>';
        $table .= '<tr>';
        foreach ($this->_data[0] as $key => $value) {
            $table .= '<th>' . $key . '</th>';
        }
        $table .= '</tr>';
        $table .= '</thead>';
        $table .= '<tbody>';
        foreach ($this->_data as $key => $value) {
            $table .= '<tr>';
            foreach ($value as $key2 => $value2) {
                $table .= '<td>' . $value2 . '</td>';
            }
            // $table .= '<td>' . $value . '</td>';
            $table .= '</tr>';
        }
        $table .= '</tbody>';
        $table .= '</table>';
        return $table;
    }

}