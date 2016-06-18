<?php

class Database {

    // Dabatabse serverinə qoşulmaq üçün host address dəyişkəni
    private $db_host;
    // Dabatabse serverinə qoşulmaq üçün istifadəçi adı dəyişkəni
    private $db_user;
    // Dabatabse qoşulmaq üçün şifrə dəyişkəni
    private $db_pass;
    // Dabatabse serverinə qoşulduqdan sonra istifadə ediləcək datatabase-ın dəyişəni
    private $db_name;
    // Database bağlantısını olub olmadığını yoxlamaq üçün lazım olacaq dəyişkən
    private $con;
    // Database-dən gələcək nəticələri saxlamaq üçün lazım olan array
    private $result = array();
    // Database gələcək melumatların sayını tutcaq dəyişək
    private $numResults;
    //connection dəyişkəni
    private $connection;

    // Qurucu funkiyamızda Server ünvanı, istifadəçi adı, şifrə və database adını istəyirik ki bağlantı qura bilək və işlərimizi görə bilək
    public function __construct($db_host, $db_user, $db_pass, $db_name) {
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_pass = $db_pass;
        $this->db_name = $db_name;
    }

    // Qurucu mənimtədiyimiz Host məlumatlarına görə bağlantı quracaq funksiyamız
    public function connect() {
        if (!$this->con) {
            $this->connection = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name);
            if ($this->connection) {
                $this->con = TRUE;
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    // Qurulmuş olan bağlantı əgər varsa bağlantıyı bağlayacaq funksiya
    public function disconnect() {
        if ($this->con) {
            if ($this->connection->close()) {
                $this->con = false;
                return true;
            } else {
                return false;
            }
        }
    }

    // Bütün crud əməliyyatları table-ə görə olur ama hamsını yoxlamaq lazımdırkı əgər table bizim databaseizdə var iş görək yoxsa false döndərək
    // Bu yoxlamanı edəcək funksiya
    private function tableExists($table) {
        $tablesInDb = $this->connection->query('SHOW TABLES FROM ' . $this->db_name . ' LIKE "' . $table . '"');
        if ($tablesInDb) {
            if ($tablesInDb->num_rows == 1) {
                return true;
            } else {
                return false;
            }
        }
    }

    // Table-dən məlumatları oxuyacaq funkiya
    // Birinci parametr table adıdır. Təkçə table adı göndərilsə, o table-də olacaq bütün məlumatları gətirəcək. Ex == $db->select('student')
    // Əgər table adı və rows göndərilsə istənilən rowları gətirləcək ama göndərilməsə * olaraq hamısını gətirəcək. Ex == $db->select('student','name,surname')
    // Where göndərilməsə null dur. Ama göndərilsə o şərtlərə görə məlumatları gətirəcək. EX == $db->select('student','*','id<5')
    // Order normalda null-dur . Ama göndərilsə o şərtə uygun nəticələri sıralaaycaq. EX ==  $db->select('student','*',null,'id desc');
    public function select($table, $rows = '*', $where = null, $order = null) {
        $q = 'SELECT ' . $rows . ' FROM ' . $table;
        if ($where != null) {
            $q .= ' WHERE ' . $where;
        }
        if ($order != null) {
            $q .= ' ORDER BY ' . $order;
        }
        if ($this->tableExists($table)) {
            $query = $this->connection->query($q);
            if ($query) {
                $this->numResults = $query->num_rows;
                for ($i = 0; $i < $this->numResults; $i++) {
//                    fetch_assoc yolu ile
//
//                    $r = $query->fetch_assoc();
//                    $this->result[] = $r;
//
                    // Fethc array yolunu ile

                    $r = $query->fetch_array();
                    $key = array_keys($r);
                    for ($x = 0; $x < count($key); $x++) {
                        if (!is_int($key[$x])) {
                            if ($this->numResults > 1) {
                                $this->result[$i][$key[$x]] = $r[$key[$x]];
                            } else if ($this->numResults < 1) {
                                $this->result = null;
                            } else {
                                $this->result[$key[$x]] = $r[$key[$x]];
                            }
                        }
                    }
                }
                return true;
            } else {
                return false;
            }
        } else
            return false;
    }

    // Table-lə məlumat əlavə etmək üçün istifadə eləcək funskiya
    // Birinci parametr table adıdır. Hansı table-də iş görüləcəksə onu göndərilir
    // Value isə array olaraq göndələcək tabledə qarşı gələnı əlavə edəcək. EX == $db->insert('student',array(5,"dasda","Nasib",'055 629 8878'))
    // Yalnız value göndəriləndə idnidə göndərməlisiz yosa sıra itər ama Roüs da göndərsəz qarşılıq olara götürüb keyinə görə value əlavə edəcək. EX == $db->insert('student',array("dasda","Nasib",'055 629 8878'),array('name','surname','phone'));
    public function insert($table, $values, $rows = null) {
        if ($this->tableExists($table)) {
            $insert = 'INSERT INTO ' . $table;
            if ($rows != null) {
                $insert .= ' (';
                $rows = implode(',', $rows);
                $insert .= $rows;
                $insert .= ' )';
            }

            for ($i = 0; $i < count($values); $i++) {
                $values[$i] = '"' . $values[$i] . '"';
            }

            $values = implode(',', $values);
            $insert .= ' VALUES (' . $values . ')';
            $ins = $this->connection->query($insert);
            if ($ins) {
                return true;
            } else {
                return false;
            }
        }
    }

    // Table-də olandan bütün məlumatları vəya şərtə görə məlumatı silməcək funksiya
    // Əgər təkçə table adı göndərilsə bütün məlumatları siləcək EX == $db->delete('student')
    // Əgər where göndərilsə o şərtə uygun olanları siləcək  EX == $db->delete('student','id=25')
    public function delete($table, $where = null) {
        if ($this->tableExists($table)) {
            if ($where == null) {
                $delete = 'DELETE FROM ' . $table;
            } else {
                $delete = 'DELETE FROM ' . $table . ' WHERE ' . $where;
            }
            $del = $this->connection->query($delete);

            if ($del) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // Table-ədəki məlumatları yeniləyəcək funskiya
    // Funckiya 3 parametr götürür birinci table adı, sonra dəyişəcək rowlar və dəyişmək şərti
    // EX == $db->update('student',array('name'=>'Yolchu1','surname'=>'Nasib1'),array('id',1));
    public function update($table, $rows, $where) {
        if ($this->tableExists($table)) {
            for ($i = 0; $i < count($where); $i++) {
                if ($i % 2 != 0) {
                    if (is_string($where[$i])) {
                        if (($i + 1) != null)
                            $where[$i] = '"' . $where[$i] . '" AND ';
                        else
                            $where[$i] = '"' . $where[$i] . '"';
                    }
                }
            }
            $where = implode('=', $where);


            $update = 'UPDATE ' . $table . ' SET ';
            $keys = array_keys($rows);
            for ($i = 0; $i < count($rows); $i++) {
                if (is_string($rows[$keys[$i]])) {
                    $update .= $keys[$i] . '="' . $rows[$keys[$i]] . '"';
                } else {
                    $update .= $keys[$i] . '=' . $rows[$keys[$i]];
                }

                if ($i != count($rows) - 1) {
                    $update .= ',';
                }
            }
            $update .= ' WHERE ' . $where;
            $query = $this->connection->query($update);
            if ($query) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // Selec-də result propersitnə doldurulmuş məlumatları əldə etmək üçün yarayacaq funskiya
    // EX
    // $db->select('student');
    // $result = $db->getResult();
    public function getResult() {
        return $this->result;
    }

}

?>