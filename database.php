<?php

class Database
{

  private $db_host = "localhost";
  private $db_user = "root";
  private $db_pass = "";
  private $db_name = "dhothar_software";

  private $mysqli = "";
  private $result = array();
  private $conn = false;

  public function __construct()
  {
    if (!$this->conn) {
      $this->mysqli = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name);
      $this->conn = true;
      if ($this->mysqli->connect_error) {
        array_push($this->result, $this->mysqli->connect_error);
        return false;
      }
    } else {
      return true;
    }
  }

  // Function to insert into the database
  public function insert($table, $params = array())
  {
    // Check to see if the table exists
    if ($this->tableExists($table)) {
      // Seperate $params's Array KEYs and VALUEs and Convert them to String Value
      $table_columns = implode(', ', array_keys($params));
      $table_value = implode("', '", $params);

      $sql = "INSERT INTO $table ($table_columns) VALUES ('$table_value')";
      // Make the query to insert to the database
      if ($this->mysqli->query($sql)) {
        array_push($this->result, $this->mysqli->insert_id);
        return true; // The data has been inserted
      } else {
        array_push($this->result, $this->mysqli->error);
        return false; // The data has not been inserted
      }

    } else {
      return false; // Table does not exist
    }
  }
  // Function to get the last inserted ID
  public function lastInsertedId()
  {
    return $this->mysqli->insert_id;
  }

  public function count($table, $column = '*', $where = null)
  {
    if ($this->tableExists($table)) {
      $sql = "SELECT COUNT($column) AS total FROM $table";
      if ($where != null) {
        $sql .= " WHERE $where";
      }

      $query = $this->mysqli->query($sql);

      if ($query) {
        $result = $query->fetch_assoc();
        return $result['total']; // Return the count value
      } else {
        array_push($this->result, $this->mysqli->error);
        return false;
      }
    } else {
      return false; // Table does not exist
    }
  }

  // Function to count rows returned
  public function numRows($sql)
  {
    $query = $this->mysqli->query($sql);
    return ($query) ? $query->num_rows : 0;
  }
  // Function to update row in database
  public function update($table, $params = array(), $where = null)
  {
    // Check to see if table exists
    if ($this->tableExists($table)) {
      // Create Array to hold all the columns to update
      $args = array();
      foreach ($params as $key => $value) {
        $args[] = "$key = '$value'"; // Seperate each column out with it's corresponding value
      }

      $sql = "UPDATE $table SET " . implode(', ', $args);
      if ($where != null) {
        $sql .= " WHERE $where";
      }
      // Make query to database
      if ($this->mysqli->query($sql)) {
        array_push($this->result, $this->mysqli->affected_rows);
        return true; // Update has been successful
      } else {
        array_push($this->result, $this->mysqli->error);
        return false; // Update has not been successful
      }
    } else {
      return false; // The table does not exist
    }
  }

  //Function to delete table or row(s) from database
  public function delete($table, $where = null)
  {
    // Check to see if table exists
    if ($this->tableExists($table)) {
      $sql = "DELETE FROM $table";  // Create query to delete rows
      if ($where != null) {
        $sql .= " WHERE $where";
      }
      // Submit query to database
      if ($this->mysqli->query($sql)) {
        array_push($this->result, $this->mysqli->affected_rows);
        return true; // The query exectued correctly
      } else {
        array_push($this->result, $this->mysqli->error);
        return false; // The query did not execute correctly
      }

    } else {
      return false; // The table does not exist
    }
  }

  // Function to SELECT from the database
  public function select($table, $rows = "*", $join = null, $where = null, $order = null, $limit = null, $debug = false)
  {
    // Check if table exists
    if ($this->tableExists($table)) {
      // Build the SQL query
      $sql = "SELECT $rows FROM $table";
      if ($join != null) {
        // If $join already starts with LEFT/RIGHT/INNER/FULL, then don't prepend "JOIN"
        if (preg_match('/^(LEFT|RIGHT|INNER|FULL)\s+JOIN/i', $join)) {
          $sql .= " $join";
        } else {
          $sql .= " JOIN $join";
        }
      }
      if ($where != null) {
        $sql .= " WHERE $where";
      }
      if ($order != null) {
        $sql .= " ORDER BY $order";
      }
      if ($limit != null) {
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $start = ($page - 1) * $limit;
        $sql .= " LIMIT $start, $limit";
      }

      // **Print query only if debug is enabled**
      if ($debug) {
        echo "<pre>SQL Query: $sql</pre>";
      }

      $query = $this->mysqli->query($sql);

      if ($query) {
        $this->result = $query->fetch_all(MYSQLI_ASSOC);
        return true; // Query was successful
      } else {
        array_push($this->result, $this->mysqli->error);
        return false; // No rows returned
      }
    } else {
      return false; // Table does not exist
    }
  }


  // FUNCTION to show Pagination
  public function pagination($table, $join = null, $where = null, $limit = null)
  {
    // Check to see if table exists
    if ($this->tableExists($table)) {
      if ($limit != null) {
        // select count() query for pagination
        $sql = "SELECT COUNT(*) FROM $table";
        if ($join != null) {
          $sql .= " JOIN $join";
        }
        if ($where != null) {
          $sql .= " WHERE $where";
        }

        $query = $this->mysqli->query($sql);

        $total_record = $query->fetch_array();
        $total_record = $total_record[0];

        $total_page = ceil($total_record / $limit);

        $url = basename($_SERVER['PHP_SELF']);
        // Get the Page Number which is set in URL
        if (isset($_GET['page'])) {
          $page = $_GET['page'];
        } else {
          $page = 1;
        }
        // show pagination
        $output = "<ul class='pagination'>";

        if ($page > 1) {
          $output .= "<li><a href='$url?page=" . ($page - 1) . "'>Prev</a></li>";
        }

        if ($total_record > $limit) {
          for ($i = 1; $i <= $total_page; $i++) {
            if ($i == $page) {
              $cls = "class='active'";
            } else {
              $cls = "";
            }
            $output .= "<li><a $cls href='$url?page=$i'>$i</a></li>";
          }
        }
        if ($total_page > $page) {
          $output .= "<li><a href='$url?page=" . ($page + 1) . "'>Next</a></li>";
        }
        $output .= "</ul>";

        echo $output;

      } else {
        return false; // If Limit is null
      }
    } else {
      return false; // Table does not exist
    }
  }

  public function sql($sql)
  {
    $query = $this->mysqli->query($sql);

    if ($query) {
      $this->result = $query->fetch_all(MYSQLI_ASSOC);
      return true; // Query was successful
    } else {
      array_push($this->result, $this->mysqli->error);
      return false; // No rows were returned
    }
  }

  // Private function to check if table exists for use with queries
  private function tableExists($table)
  {
    $sql = "SHOW TABLES FROM $this->db_name LIKE '$table'";
    $tableInDb = $this->mysqli->query($sql);
    if ($tableInDb) {
      if ($tableInDb->num_rows == 1) {
        return true; // The table exists
      } else {
        array_push($this->result, $table . " does not exist in this database.");
        return false; // The table does not exist
      }
    }
  }

  // Public function to return the data to the user
  public function getResult()
  {
    $val = $this->result;
    $this->result = array();
    return $val;
  }

  // close connection
  public function __destruct()
  {
    if ($this->conn) {
      if ($this->mysqli->close()) {
        $this->conn = false;
        return true;
      }
    } else {
      return false;
    }
  }

  // 🔹 Reusable function to get old record data
  public function getOldData($table, $idColumn, $idValue)
  {
    $stmt = $this->mysqli->prepare("SELECT * FROM $table WHERE $idColumn = ?");
    $stmt->bind_param('s', $idValue);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data ?: [];
  }
  // ===================== AUDIT LOG FUNCTION =====================
  public function logChanges($table, $record_id, $oldData, $newData, $user_id, $ignore = ['id', 'created_at', 'updated_at'])
  {
    $fields = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

    foreach ($fields as $field) {
      if (in_array($field, $ignore))
        continue;

      $oldValue = isset($oldData[$field]) ? trim((string) $oldData[$field]) : null;
      $newValue = isset($newData[$field]) ? trim((string) $newData[$field]) : null;

      if (
        $oldValue !== $newValue &&
        !is_null($oldValue) &&
        !is_null($newValue) &&
        $newValue !== ''
      ) {

        $stmt = $this->mysqli->prepare("
                  INSERT INTO audit_log (user_id, table_name, record_id, field_name, old_value, new_value)
                  VALUES (?, ?, ?, ?, ?, ?)
              ");
        if ($stmt) {
          $stmt->bind_param('ssssss', $user_id, $table, $record_id, $field, $oldValue, $newValue);
          $stmt->execute();
          $stmt->close();
        } else {
          error_log("Audit log prepare failed: " . $this->mysqli->error);
        }
      }
    }
  }



} //Class Close


?>