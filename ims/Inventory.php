<?php
class Inventory {
    private $host  = 'localhost';
    private $user  = 'root';
    private $password   = '';
    private $database  = 'ims_db';   
	private $userTable = 'ims_user';	
    private $customerTable = 'ims_customer';
	private $categoryTable = 'ims_category';
	private $placeTable = 'place';
	private $brandTable = 'ims_brand';
	private $productTable = 'ims_product';
	private $supplierTable = 'ims_supplier';
	private $purchaseTable = 'ims_purchase';
	private $orderTable = 'ims_order';
	private $dbConnect = false;
    public function __construct(){
        if(!$this->dbConnect){ 
            $conn = new mysqli($this->host, $this->user, $this->password, $this->database);
            if($conn->connect_error){
                die("Error failed to connect to MySQL: " . $conn->connect_error);
            }else{
                $this->dbConnect = $conn;
            }
        }
    }
	private function getData($sqlQuery) {
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		if(!$result){
			die('Error in query: '. mysqli_error());
		}
		$data= array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$data[]=$row;            
		}
		return $data;
	}
	private function getNumRows($sqlQuery) {
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		if(!$result){
			die('Error in query: '. mysqli_error());
		}
		$numRows = mysqli_num_rows($result);
		return $numRows;
	}
	public function login($email, $password){
		$password = md5($password);
		$sqlQuery = "
			SELECT userid, email, password, name, type, status
			FROM ".$this->userTable." 
			WHERE email='".$email."' AND password='".$password."'";
        return  $this->getData($sqlQuery);
	}	
	public function checkLogin(){
		if(empty($_SESSION['userid'])) {
			header("Location:login.php");
		}
	}

	// Customer functions
	public function getCustomer(){
		$sqlQuery = "
			SELECT * FROM ".$this->customerTable." 
			WHERE id = '".$_POST["userid"]."'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		echo json_encode($row);
	}
	
	public function getCustomerList(){		
		$sqlQuery = "SELECT * FROM ".$this->customerTable." ";
		if(!empty($_POST["search"]["value"])){
			$sqlQuery .= '(id LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= '(name LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR address LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR mobile LIKE "%'.$_POST["search"]["value"].'%") ';
			$sqlQuery .= 'OR balance LIKE "%'.$_POST["search"]["value"].'%") ';
		}
		if(!empty($_POST["order"])){
			$sqlQuery .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
		} else {
			$sqlQuery .= 'ORDER BY id DESC ';
		}
		if($_POST["length"] != -1){
			$sqlQuery .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}	
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$numRows = mysqli_num_rows($result);
		$customerData = array();	
		while( $customer = mysqli_fetch_assoc($result) ) {		
			$customerRows = array();
			$customerRows[] = $customer['id'];
			$customerRows[] = $customer['name'];
			$customerRows[] = $customer['address'];			
			$customerRows[] = $customer['mobile'];	
			$customerRows[] = number_format($customer['balance'],2);	
			$customerRows[] = '<button type="button" name="update" id="'.$customer["id"].'" class="btn btn-primary btn-sm rounded-0 update" title="update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="'.$customer["id"].'" class="btn btn-danger btn-sm rounded-0 delete" ><i class="fa fa-trash"></button>';
			$customerRows[] = '';
			$customerData[] = $customerRows;
		}
		$output = array(
			"draw"				=>	intval($_POST["draw"]),
			"recordsTotal"  	=>  $numRows,
			"recordsFiltered" 	=> 	$numRows,
			"data"    			=> 	$customerData
		);
		echo json_encode($output);
	}

	public function saveCustomer() {		
		$sqlInsert = "
			INSERT INTO ".$this->customerTable."(name, address, mobile, balance) 
			VALUES ('".$_POST['cname']."', '".$_POST['address']."', '".$_POST['mobile']."', '".$_POST['balance']."')";		
		mysqli_query($this->dbConnect, $sqlInsert);
		echo 'New Customer Added';
	}			
	public function updateCustomer() {
		if($_POST['userid']) {	
			$sqlInsert = "
				UPDATE ".$this->customerTable." 
				SET name = '".$_POST['cname']."', address= '".$_POST['address']."', mobile = '".$_POST['mobile']."', balance = '".$_POST['balance']."' 
				WHERE id = '".$_POST['userid']."'";		
			mysqli_query($this->dbConnect, $sqlInsert);	
			echo 'Customer Edited';
		}	
	}	
	public function deleteCustomer(){
		$sqlQuery = "
			DELETE FROM ".$this->customerTable." 
			WHERE id = '".$_POST['userid']."'";		
		mysqli_query($this->dbConnect, $sqlQuery);		
	}

	// Place functions
	public function getPlaceList(){		
		$sqlQuery = "SELECT * FROM ".$this->placeTable." ";
		if(!empty($_POST["search"]["value"])){
			$sqlQuery .= 'WHERE (placeName LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR placeCode LIKE "%'.$_POST["search"]["value"].'%") ';			
		}
		if(!empty($_POST["order"])){
			$sqlQuery .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
		} else {
			$sqlQuery .= 'ORDER BY placeCode DESC ';
		}
		if($_POST["length"] != -1){
			$sqlQuery .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}	
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$numRows = mysqli_num_rows($result);
		$placeData = array();	
		while( $place = mysqli_fetch_assoc($result) ) {		
			$placeRows = array();
			$status = '';
			if($place['status'] == 'active')	{
				$status = '<span class="label label-success">Active</span>';
			} else {
				$status = '<span class="label label-danger">Inactive</span>';
			}
			$placeyRows[] = $place['placeCode'];
			$placeRows[] = $place['placeName'];
			$placeRows[] = $status;			
			$placeRows[] = '<button type="button" name="update" id="'.$place["placeCode"].'" class="btn btn-primary btn-sm rounded-0 update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="'.$place["placeCode"].'" class="btn btn-danger btn-sm rounded-0 delete"  title="Delete"><i class="fa fa-trash"></i></button>';
			$placeData[] = $placeRows;
		}
		$output = array(
			"draw"				=>	intval($_POST["draw"]),
			"recordsTotal"  	=>  $numRows,
			"recordsFiltered" 	=> 	$numRows,
			"data"    			=> 	$categoryData
		);
		echo json_encode($output);
	}
	public function savePlace() {		
		$sqlInsert = "
			INSERT INTO ".$this->placeTable."(name) 
			VALUES ('".$_POST['place']."')";		
		mysqli_query($this->dbConnect, $sqlInsert);
		echo 'New Place Added';
	}	
	public function getPlace(){
		$sqlQuery = "
			SELECT * FROM ".$this->placeTable." 
			WHERE categoryid = '".$_POST["categoryId"]."'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		echo json_encode($row);
	}
	public function updatePlace() {
		if($_POST['category']) {	
			$sqlInsert = "
				UPDATE ".$this->categoryTable." 
				SET name = '".$_POST['category']."'
				WHERE categoryid = '".$_POST["categoryId"]."'";	
			mysqli_query($this->dbConnect, $sqlInsert);	
			echo 'Category Update';
		}	
	}	
	public function deletePlace(){
		$sqlQuery = "
			DELETE FROM ".$this->categoryTable." 
			WHERE categoryid = '".$_POST["categoryId"]."'";		
		mysqli_query($this->dbConnect, $sqlQuery);		
	}

	// Category functions
	public function getCategoryList(){		
		$sqlQuery = "SELECT * FROM ".$this->categoryTable." ";
		if(!empty($_POST["search"]["value"])){
			$sqlQuery .= 'WHERE (name LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR status LIKE "%'.$_POST["search"]["value"].'%") ';			
		}
		if(!empty($_POST["order"])){
			$sqlQuery .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
		} else {
			$sqlQuery .= 'ORDER BY categoryid DESC ';
		}
		if($_POST["length"] != -1){
			$sqlQuery .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}	
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$numRows = mysqli_num_rows($result);
		$categoryData = array();	
		while( $category = mysqli_fetch_assoc($result) ) {		
			$categoryRows = array();
			$status = '';
			if($category['status'] == 'active')	{
				$status = '<span class="label label-success">Active</span>';
			} else {
				$status = '<span class="label label-danger">Inactive</span>';
			}
			$categoryRows[] = $category['categoryid'];
			$categoryRows[] = $category['name'];
			$categoryRows[] = $status;			
			$categoryRows[] = '<button type="button" name="update" id="'.$category["categoryid"].'" class="btn btn-primary btn-sm rounded-0 update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="'.$category["categoryid"].'" class="btn btn-danger btn-sm rounded-0 delete"  title="Delete"><i class="fa fa-trash"></i></button>';
			$categoryData[] = $categoryRows;
		}
		$output = array(
			"draw"				=>	intval($_POST["draw"]),
			"recordsTotal"  	=>  $numRows,
			"recordsFiltered" 	=> 	$numRows,
			"data"    			=> 	$categoryData
		);
		echo json_encode($output);
	}
	public function saveCategory() {		
		$sqlInsert = "
			INSERT INTO ".$this->categoryTable."(name) 
			VALUES ('".$_POST['category']."')";		
		mysqli_query($this->dbConnect, $sqlInsert);
		echo 'New Category Added';
	}	
	public function getCategory(){
		$sqlQuery = "
			SELECT * FROM ".$this->categoryTable." 
			WHERE categoryid = '".$_POST["categoryId"]."'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		echo json_encode($row);
	}
	public function updateCategory() {
		if($_POST['category']) {	
			$sqlInsert = "
				UPDATE ".$this->categoryTable." 
				SET name = '".$_POST['category']."'
				WHERE categoryid = '".$_POST["categoryId"]."'";	
			mysqli_query($this->dbConnect, $sqlInsert);	
			echo 'Category Update';
		}	
	}	
	public function deleteCategory(){
		$sqlQuery = "
			DELETE FROM ".$this->categoryTable." 
			WHERE categoryid = '".$_POST["categoryId"]."'";		
		mysqli_query($this->dbConnect, $sqlQuery);		
	}
	// Brand management 
	public function getBrandList(){				
		$sqlQuery = "SELECT * FROM ".$this->brandTable." as b 
			INNER JOIN ".$this->categoryTable." as c ON c.categoryid = b.categoryid ";
		if(!empty($_POST["search"]["value"])){
			$sqlQuery .= 'WHERE b.bname LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR c.name LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR b.status LIKE "%'.$_POST["search"]["value"].'%" ';		
		}
		if(!empty($_POST["order"])){
			$sqlQuery .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
		} else {
			$sqlQuery .= 'ORDER BY b.id DESC ';
		}
		if($_POST["length"] != -1){
			$sqlQuery .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}	
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$numRows = mysqli_num_rows($result);
		$brandData = array();	
		while( $brand = mysqli_fetch_assoc($result) ) {			
			$status = '';
			if($brand['status'] == 'active')	{
				$status = '<span class="label label-success">Active</span>';
			} else {
				$status = '<span class="label label-danger">Inactive</span>';
			}
			$brandRows = array();
			$brandRows[] = $brand['id'];
			$brandRows[] = $brand['bname'];
			$brandRows[] = $brand['name'];
			$brandRows[] = $status;
			$brandRows[] = '<button type="button" name="update" id="'.$brand["id"].'" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="'.$brand["id"].'" class="btn btn-danger btn-sm rounded-0  delete" data-status="'.$brand["status"].'" title="Delete"><i class="fa fa-trash"></i></button>';
			$brandData[] = $brandRows;
		}
		$output = array(
			"draw"				=>	intval($_POST["draw"]),
			"recordsTotal"  	=>  $numRows,
			"recordsFiltered" 	=> 	$numRows,
			"data"    			=> 	$brandData
		);
		echo json_encode($output);
	}
	public function categoryDropdownList(){		
		$sqlQuery = "SELECT * FROM ".$this->categoryTable." 
			WHERE status = 'active' 
			ORDER BY name ASC";	
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$categoryHTML = '';
		while( $category = mysqli_fetch_assoc($result)) {
			$categoryHTML .= '<option value="'.$category["categoryid"].'">'.$category["name"].'</option>';	
		}
		return $categoryHTML;
	}
	public function placeDropdownList(){		
		$sqlQuery = "SELECT * FROM ".$this->placeTable;
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$placeHTML = '';
		while( $place = mysqli_fetch_assoc($result)) {
			$placeHTML .= '<option value="'.$place["placeCode"].'">'.$place["placeName"].'</option>';	
		}
		return $placeHTML;
	}
	public function saveBrand() {		
		$sqlInsert = "
			INSERT INTO ".$this->brandTable."(categoryid, bname) 
			VALUES ('".$_POST["categoryid"]."', '".$_POST['bname']."')";		
		mysqli_query($this->dbConnect, $sqlInsert);
		echo 'New Brand Added';
	}	
	public function getBrand(){
		$sqlQuery = "
			SELECT * FROM ".$this->brandTable." 
			WHERE id = '".$_POST["id"]."'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		echo json_encode($row);
	}	
	public function updateBrand() {		
		if($_POST['id']) {	
			$sqlUpdate = "UPDATE ".$this->brandTable." SET bname = '".$_POST['bname']."', categoryid='".$_POST['categoryid']."' WHERE id = '".$_POST["id"]."'";
			mysqli_query($this->dbConnect, $sqlUpdate);	
			echo 'Brand Update';
		}	
	}	
	public function deleteBrand(){
		$sqlQuery = "
			DELETE FROM ".$this->brandTable." 
			WHERE id = '".$_POST["id"]."'";	
		mysqli_query($this->dbConnect, $sqlQuery);		
	}
	// Product management 
	public function getProductList(){				
		$sqlQuery = "SELECT * FROM ".$this->productTable." as p
			INNER JOIN ".$this->brandTable." as b ON b.id = p.brandid
			INNER JOIN ".$this->categoryTable." as c ON c.categoryid = p.categoryid 
			INNER JOIN ".$this->supplierTable." as s ON s.supplier_id = p.supplier ";
		if(isset($_POST["search"]["value"])) {
			$sqlQuery .= 'WHERE b.bname LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR c.name LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR p.pname LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR p.model LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR p.quantity LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR s.supplier_name LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= 'OR p.pid LIKE "%'.$_POST["search"]["value"].'%" ';
		}
		if(isset($_POST['order'])) {
			$sqlQuery .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
		} else {
			$sqlQuery .= 'ORDER BY p.pid DESC ';
		}
		if($_POST['length'] != -1) {
			$sqlQuery .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}		
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$numRows = mysqli_num_rows($result);
		$productData = array();	
		while( $product = mysqli_fetch_assoc($result) ) {			
			$status = '';
			if($product['status'] == 'active') {
				$status = '<span class="label label-success">Active</span>';
			} else {
				$status = '<span class="label label-danger">Inactive</span>';
			}
			$productRow = array();
			$productRow[] = $product['pid'];
			$productRow[] = $product['name'];
			$productRow[] = $product['bname'];
			$productRow[] = $product['pname'];	
			$productRow[] = $product['model'];
			$productRow[] = $product["quantity"];
			$productRow[] = $product['supplier_name'];
			$productRow[] = $product['active'];
			$productRow[] = $product['inactive'];
			$productRow[] = '<div class="btn-group btn-group-sm"><button type="button" name="view" id="'.$product["pid"].'" class="btn btn-light bg-gradient border text-dark btn-sm rounded-0  view" title="View"><i class="fa fa-eye"></i></button><button type="button" name="update" id="'.$product["pid"].'" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="'.$product["pid"].'" class="btn btn-danger btn-sm rounded-0  delete" data-status="'.$product["status"].'" title="Delete"><i class="fa fa-trash"></i></button></div>';
			$productData[] = $productRow;
						
		}
		$outputData = array(
			"draw"    			=> 	intval($_POST["draw"]),
			"recordsTotal"  	=>  $numRows,
			"recordsFiltered" 	=> 	$numRows,
			"data"    			=> 	$productData
		);
		echo json_encode($outputData);
	}
	public function getCategoryBrand($categoryid){	
		$sqlQuery = "SELECT * FROM ".$this->brandTable." 
			WHERE status = 'active' AND categoryid = '".$categoryid."'	ORDER BY bname ASC";
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$dropdownHTML = '';
		while( $brand = mysqli_fetch_assoc($result) ) {	
			$dropdownHTML .= '<option value="'.$brand["id"].'">'.$brand["bname"].'</option>';
		}
		return $dropdownHTML;
	}
	public function supplierDropdownList(){	
		$sqlQuery = "SELECT * FROM ".$this->supplierTable." 
			WHERE status = 'active'	ORDER BY supplier_name ASC";
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$dropdownHTML = '';
		while( $supplier = mysqli_fetch_assoc($result) ) {	
			$dropdownHTML .= '<option value="'.$supplier["supplier_id"].'">'.$supplier["supplier_name"].'</option>';
		}
		return $dropdownHTML;
	}
	public function addProduct() {		
		$folioNum = "UV/FAS/Phy/" . $_POST['placeid'] . "/" . $_POST['folioNum'];
		$sqlInsert = "
			INSERT INTO " . $this->productTable . "(categoryid, brandid, pname, model, description, quantity, unit, base_price, supplier, active, inactive) 
               VALUES ('" . $_POST["categoryid"] . "', '" . $_POST['brandid'] . "', '" . $_POST['pname'] . "', '" . $folioNum . "', '" . $_POST['description'] . "', '" . $_POST['quantity'] . "', '" . $_POST['unit'] . "', '" . $_POST['base_price'] . "', '" . $_POST['supplierid'] . "', '" . $_POST['active'] . "', '" . $_POST['inactive'] . "')";
		mysqli_query($this->dbConnect, $sqlInsert);
		echo 'New Product Added';
	}	
	public function getProductDetails(){
		$sqlQuery = "
			SELECT * FROM ".$this->productTable." 
			WHERE pid = '".$_POST["pid"]."'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);			
		while( $product = mysqli_fetch_assoc($result)) {
			$output['pid'] = $product['pid'];
			$output['categoryid'] = $product['categoryid'];
			$output['brandid'] = $product['brandid'];
			$output["brand_select_box"] = $this->getCategoryBrand($product['categoryid']);
			$output['pname'] = $product['pname'];
			$output['model'] = $product['model'];
			//$output['place'] = $product['place'];
			$output['description'] = $product['description'];
			$output['quantity'] = $product['quantity'];
			$output['unit'] = $product['unit'];
			$output['base_price'] = $product['base_price'];
			$output['supplier'] = $product['supplier'];
			$output['active'] = $product['active'];
			$output['inactive'] = $product['inactive'];
			
		}
		echo json_encode($output);
	}
	public function updateProduct() {		
		if($_POST['pid']) {	
			$folioNum = "UV/FAS/Phy/" . $_POST['folioNum'];
			$sqlUpdate = "UPDATE ".$this->productTable." 
				SET categoryid = '".$_POST['categoryid']."', brandid='".$_POST['brandid']."', pname='".$_POST['pname']."', model='" . $folioNum . "', description='".$_POST['description']."', quantity='".$_POST['quantity']."', unit='".$_POST['unit']."', base_price='".$_POST['base_price']."', supplier='".$_POST['supplierid']."', active='".$_POST['active']."', inactive='".$_POST['inactive']."' WHERE pid = '".$_POST["pid"]."'";			
			mysqli_query($this->dbConnect, $sqlUpdate);	
			echo 'Product Update';
		}	
	}	
	public function deleteProduct(){
		$sqlQuery = "
			DELETE FROM ".$this->productTable." 
			WHERE pid = '".$_POST["pid"]."'";	
		mysqli_query($this->dbConnect, $sqlQuery);		
	}	
	public function viewProductDetails(){
		$sqlQuery = "SELECT * FROM ".$this->productTable." as p
			INNER JOIN ".$this->brandTable." as b ON b.id = p.brandid
			INNER JOIN ".$this->categoryTable." as c ON c.categoryid = p.categoryid 
			INNER JOIN ".$this->supplierTable." as s ON s.supplier_id = p.supplier 
			WHERE p.pid = '".$_POST["pid"]."'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$productDetails = '<div class="table-responsive">
				<table class="table table-boredered">';
		while( $product = mysqli_fetch_assoc($result) ) {
			$status = '';
			if($product['status'] == 'active') {
				$status = '<span class="label label-success">Active</span>';
			} else {
				$status = '<span class="label label-danger">Inactive</span>';
			}
			$productDetails .= '
			<tr>
				<td>Product Name</td>
				<td>'.$product["pname"].'</td>
			</tr>
			<tr>
				<td>Product Model</td>
				<td>'.$product["model"].'</td>
			</tr>
			<tr>
				<td>Product Description</td>
				<td>'.$product["description"].'</td>
			</tr>
			<tr>
				<td>Category</td>
				<td>'.$product["name"].'</td>
			</tr>
			<tr>
				<td>Brand</td>
				<td>'.$product["bname"].'</td>
			</tr>			
			<tr>
				<td>Available Quantity</td>
				<td>'.$product["quantity"].' '.$product["unit"].'</td>
			</tr>
			<tr>
				<td>Base Price</td>
				<td>'.$product["base_price"].'</td>
			</tr>
			<tr>
				<td>Enter By</td>
				<td>'.$product["supplier_name"].'</td>
			</tr>
			<tr>
				<td>active</td>
				<td>'.$product["active"].'</td>
			</tr>
			<tr>
				<td>inactive</td>
				<td>'.$product["inactive"].'</td>
			</tr>
			';
		}
		$productDetails .= '
			</table>
		</div>
		';
		echo $productDetails;
	}
	// supplier 
	public function getSupplierList(){		
		$sqlQuery = "SELECT * FROM ".$this->supplierTable." ";
		if(!empty($_POST["search"]["value"])){
			$sqlQuery .= 'WHERE (supplier_name LIKE "%'.$_POST["search"]["value"].'%" ';
			$sqlQuery .= '(address LIKE "%'.$_POST["search"]["value"].'%" ';			
		}
		if(!empty($_POST["order"])){
			$sqlQuery .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
		} else {
			$sqlQuery .= 'ORDER BY supplier_id DESC ';
		}
		if($_POST["length"] != -1){
			$sqlQuery .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}	
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$numRows = mysqli_num_rows($result);
		$supplierData = array();	
		while( $supplier = mysqli_fetch_assoc($result) ) {	
			$status = '';
			if($supplier['status'] == 'active') {
				$status = '<span class="label label-success">Active</span>';
			} else {
				$status = '<span class="label label-danger">Inactive</span>';
			}
			$supplierRows = array();
			$supplierRows[] = $supplier['supplier_id'];		
			$supplierRows[] = $supplier['supplier_name'];	
			$supplierRows[] = $supplier['mobile'];			
			$supplierRows[] = $supplier['address'];	
			$supplierRows[] = $status;			
			$supplierRows[] = '<div class="btn-group btn-group-sm"><button type="button" name="update" id="'.$supplier["supplier_id"].'" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="'.$supplier["supplier_id"].'" class="btn btn-danger btn-sm rounded-0  delete"  title="Delete"><i class="fa fa-trash"></i></button></div>';
			$supplierData[] = $supplierRows;
		}
		$output = array(
			"draw"				=>	intval($_POST["draw"]),
			"recordsTotal"  	=>  $numRows,
			"recordsFiltered" 	=> 	$numRows,
			"data"    			=> 	$supplierData
		);
		echo json_encode($output);
	}
	public function addSupplier() {		
		$sqlInsert = "
			INSERT INTO ".$this->supplierTable."(supplier_name, mobile, address) 
			VALUES ('".$_POST['supplier_name']."', '".$_POST['mobile']."', '".$_POST['address']."')";		
		mysqli_query($this->dbConnect, $sqlInsert);
		echo 'New Supplier Added';
	}			
	public function getSupplier(){
		$sqlQuery = "
			SELECT * FROM ".$this->supplierTable." 
			WHERE supplier_id = '".$_POST["supplier_id"]."'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		echo json_encode($row);
	}
	public function updateSupplier() {
		if($_POST['supplier_id']) {	
			$sqlUpdate = "
				UPDATE ".$this->supplierTable." 
				SET supplier_name = '".$_POST['supplier_name']."', mobile= '".$_POST['mobile']."' , address= '".$_POST['address']."'	WHERE supplier_id = '".$_POST['supplier_id']."'";		
			mysqli_query($this->dbConnect, $sqlUpdate);	
			echo 'Supplier Edited';
		}	
	}	
	public function deleteSupplier(){
		$sqlQuery = "
			DELETE FROM ".$this->supplierTable." 
			WHERE supplier_id = '".$_POST['supplier_id']."'";		
		mysqli_query($this->dbConnect, $sqlQuery);		
	}
	// purchase
	public function listPurchase(){		
		$sqlQuery = "SELECT ph.*, p.pname, s.supplier_name FROM ".$this->purchaseTable." as ph
			INNER JOIN ".$this->productTable." as p ON p.pid = ph.product_id 
			INNER JOIN ".$this->supplierTable." as s ON s.supplier_id = ph.supplier_id ";
		if(isset($_POST['order'])) {
			$sqlQuery .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
		} else {
			$sqlQuery .= 'ORDER BY ph.purchase_id DESC ';
		}
		if($_POST['length'] != -1) {
			$sqlQuery .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}		
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$numRows = mysqli_num_rows($result);
		$purchaseData = array();	
		while( $purchase = mysqli_fetch_assoc($result) ) {			
			$productRow = array();
			$productRow[] = $purchase['purchase_id'];
			$productRow[] = $purchase['pname'];
			$productRow[] = $purchase['quantity'];			
			$productRow[] = $purchase['supplier_name'];			
			$productRow[] = '<div class="btn-group btn-group-sm"><button type="button" name="update" id="'.$purchase["purchase_id"].'" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="'.$purchase["purchase_id"].'" class="btn btn-danger btn-sm rounded-0  delete" title="Delete"><i class="fa fa-trash"></i></button></div>';
			$purchaseData[] = $productRow;
						
		}
		$output = array(
			"draw"				=>	intval($_POST["draw"]),
			"recordsTotal"  	=>  $numRows,
			"recordsFiltered" 	=> 	$numRows,
			"data"    			=> 	$purchaseData
		);
		echo json_encode($output);		
	}
	public function productDropdownList(){	
		$sqlQuery = "SELECT * FROM ".$this->productTable." ORDER BY pname ASC";
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$dropdownHTML = '';
		while( $product = mysqli_fetch_assoc($result) ) {	
			$dropdownHTML .= '<option value="'.$product["pid"].'">'.$product["pname"].'</option>';
		}
		return $dropdownHTML;
	}
	public function addPurchase() {		
		$sqlInsert = "
			INSERT INTO ".$this->purchaseTable."(product_id, quantity, supplier_id) 
			VALUES ('".$_POST['product']."', '".$_POST['quantity']."', '".$_POST['supplierid']."')";		
		mysqli_query($this->dbConnect, $sqlInsert);
		echo 'New Purchase Added';
	}	
	public function getPurchaseDetails(){
		$sqlQuery = "
			SELECT * FROM ".$this->purchaseTable." 
			WHERE purchase_id = '".$_POST["purchase_id"]."'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		echo json_encode($row);
	}
	public function updatePurchase() {
		if($_POST['purchase_id']) {	
			$sqlUpdate = "
				UPDATE ".$this->purchaseTable." 
				SET product_id = '".$_POST['product']."', quantity= '".$_POST['quantity']."' , supplier_id= '".$_POST['supplierid']."'	WHERE purchase_id = '".$_POST['purchase_id']."'";		
			mysqli_query($this->dbConnect, $sqlUpdate);	
			echo 'Purchase Edited';
		}	
	}	
	public function deletePurchase(){
		$sqlQuery = "
			DELETE FROM ".$this->purchaseTable." 
			WHERE purchase_id = '".$_POST['purchase_id']."'";		
		mysqli_query($this->dbConnect, $sqlQuery);		
	}
	// order
	public function listOrders(){		
		$sqlQuery = "SELECT * FROM ".$this->orderTable." as o
			INNER JOIN ".$this->customerTable." as c ON c.id = o.customer_id
			INNER JOIN ".$this->productTable." as p ON p.pid = o.product_id ";		
		if(isset($_POST['order'])) {
			$sqlQuery .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
		} else {
			$sqlQuery .= 'ORDER BY o.order_id DESC ';
		}
		if($_POST['length'] != -1) {
			$sqlQuery .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}		
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$numRows = mysqli_num_rows($result);
		$orderData = array();	
		while( $order = mysqli_fetch_assoc($result) ) {			
			$orderRow = array();
			$orderRow[] = $order['order_id'];
			$orderRow[] = $order['pname'];
			$orderRow[] = $order['total_shipped'];	
			$orderRow[] = $order['name'];			
			$orderRow[] = '<div class="btn-group btn-group-sm"><button type="button" name="update" id="'.$order["order_id"].'" class="btn btn-primary btn-sm rounded-0  update" title="Update"><i class="fa fa-edit"></i></button><button type="button" name="delete" id="'.$order["order_id"].'" class="btn btn-danger btn-sm rounded-0  delete" title="Delete"><i class="fa fa-trash"></i></button></button';
			$orderData[] = $orderRow;
						
		}
		$output = array(
			"draw"				=>	intval($_POST["draw"]),
			"recordsTotal"  	=>  $numRows,
			"recordsFiltered" 	=> 	$numRows,
			"data"    			=> 	$orderData
		);
		echo json_encode($output);		
	}
	public function addOrder() {		
		$sqlInsert = "
			INSERT INTO ".$this->orderTable."(product_id, total_shipped, customer_id) 
			VALUES ('".$_POST['product']."', '".$_POST['shipped']."', '".$_POST['customer']."')";		
		mysqli_query($this->dbConnect, $sqlInsert);
		echo 'New order added';
	}		
	public function getOrderDetails(){
		$sqlQuery = "
			SELECT * FROM ".$this->orderTable." 
			WHERE order_id = '".$_POST["order_id"]."'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		echo json_encode($row);
	}
	public function updateOrder() {
		if($_POST['order_id']) {	
			$sqlUpdate = "
				UPDATE ".$this->orderTable." 
				SET product_id = '".$_POST['product']."', total_shipped='".$_POST['shipped']."', customer_id='".$_POST['customer']."' WHERE order_id = '".$_POST['order_id']."'";		
			mysqli_query($this->dbConnect, $sqlUpdate);	
			echo 'Order Edited';
		}	
	}	
	public function deleteOrder(){
		$sqlQuery = "
			DELETE FROM ".$this->orderTable." 
			WHERE order_id = '".$_POST['order_id']."'";		
		mysqli_query($this->dbConnect, $sqlQuery);		
	}
	public function customerDropdownList(){	
		$sqlQuery = "SELECT * FROM ".$this->customerTable." ORDER BY name ASC";
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$dropdownHTML = '';
		while( $customer = mysqli_fetch_assoc($result) ) {	
			$dropdownHTML .= '<option value="'.$customer["id"].'">'.$customer["name"].'</option>';
		}
		return $dropdownHTML;
	}
	public function getInventoryDetails(){		
		$sqlQuery = "SELECT p.pid, p.pname, p.model, p.unit, p.quantity as product_quantity, p.active as active_quantity, p.inactive as inactive_quantity, s.quantity as recieved_quantity, r.total_shipped
			FROM ".$this->productTable." as p
			LEFT JOIN ".$this->purchaseTable." as s ON s.product_id = p.pid
			LEFT JOIN ".$this->orderTable." as r ON r.product_id = p.pid ";		
		if(isset($_POST['order'])) {
			$sqlQuery .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
		} else {
			$sqlQuery .= 'ORDER BY p.pid DESC ';
		}
		if($_POST['length'] != -1) {
			$sqlQuery .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
		}		
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		$numRows = mysqli_num_rows($result);
		$inventoryData = array();	
		$i = 1;
		while( $inventory = mysqli_fetch_assoc($result) ) {	

			if(!$inventory['recieved_quantity']) {
				$inventory['recieved_quantity'] = 0;
			}
			if(!$inventory['total_shipped']) {
				$inventory['total_shipped'] = 0;
			}
			
			$inventoryInHand = ($inventory['product_quantity'] - $inventory['inactive_quantity']);
		
			$inventoryRow = array();
			$inventoryRow[] = $i++;
			$inventoryRow[] = "<div class='lh-1'><div>{$inventory['pname']}</div><div class='fw-bolder text-muted'><small>{$inventory['model']}</small></div></div>";
			// $inventoryRow[] = $inventory['pname'];
			$inventoryRow[] = $inventory['unit'];
			//$inventoryRow[] = $inventory['product_model'];
			$inventoryRow[] = $inventory['product_quantity'];	
			$inventoryRow[] = $inventory['active_quantity'];
			$inventoryRow[] = $inventory['inactive_quantity'];
			$inventoryData[] = $inventoryRow;						
		}
		$output = array(
			"draw"				=>	intval($_POST["draw"]),
			"recordsTotal"  	=>  $numRows,
			"recordsFiltered" 	=> 	$numRows,
			"data"    			=> 	$inventoryData
		);
		echo json_encode($output);		
	}
}
?>