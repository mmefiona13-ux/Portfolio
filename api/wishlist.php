<?php
require_once __DIR__.'/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405);echo json_encode(['error'=>'POST required']);exit; }
$expected=$_SESSION['csrf']??'';$provided=$_POST['csrf']??'';if (!is_string($expected)||$expected===''||!is_string($provided)||$provided===''||!hash_equals($expected,$provided)) { http_response_code(419);echo json_encode(['error'=>'Session expired']);exit; }
$id=filter_var($_POST['product_id']??null,FILTER_VALIDATE_INT);if(!$id){http_response_code(422);echo json_encode(['error'=>'Invalid product']);exit;}
try {
 if(customer_id()){if(($_POST['sync']??'')==='1'){db()->prepare('INSERT IGNORE INTO wishlist_items(account_id,product_id) SELECT ?,id FROM products WHERE id=?')->execute([customer_id(),$id]);echo json_encode(['saved'=>true,'synced'=>true]);exit;}$s=db()->prepare('SELECT 1 FROM wishlist_items WHERE account_id=? AND product_id=?');$s->execute([customer_id(),$id]);if($s->fetchColumn()){db()->prepare('DELETE FROM wishlist_items WHERE account_id=? AND product_id=?')->execute([customer_id(),$id]);$saved=false;}else{db()->prepare('INSERT IGNORE INTO wishlist_items(account_id,product_id) VALUES(?,?)')->execute([customer_id(),$id]);$saved=true;}echo json_encode(['saved'=>$saved,'synced'=>true]);}
 else {echo json_encode(['saved'=>true,'synced'=>false,'message'=>'Saved on this device. Sign in to sync your wishlist.']);}
}catch(Throwable $e){http_response_code(500);echo json_encode(['error'=>'Could not update wishlist']);}
