<?php
require 'header.php';
$defaults=['promo_message'=>'','hero_title'=>'Upgrade with genuine Samsung & iPhone devices.','hero_subtitle'=>'Explore verified new and carefully selected used phones for every lifestyle and budget.','hero_image'=>'','theme_primary'=>'#1677ff','low_stock_threshold'=>'3'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$pdo=db();$pdo->beginTransaction();$errors=[];
 foreach($defaults as $key=>$fallback){$value=trim($_POST[$key]??$fallback);
  if($key==='theme_primary'&&!preg_match('/^#[0-9a-fA-F]{6}$/',$value)){$errors[]='The theme colour must be a valid hex colour.';continue;}
  if($key==='hero_image'&&$value!==''){$parts=parse_url($value);$https=is_array($parts)&&strtolower($parts['scheme']??'')==='https'&&filter_var($value,FILTER_VALIDATE_URL);$local=preg_match('#^(assets/images|uploads/products)/[A-Za-z0-9._/-]+$#D',$value);if(!$https&&!$local){$errors[]='Hero images must use HTTPS or a local image path.';continue;}}
  if($key==='low_stock_threshold')$value=(string)max(1,min(100,(int)$value));$s=$pdo->prepare('INSERT INTO website_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');$s->execute([$key,$value]);
 }
 if($errors){$pdo->rollBack();flash('error',implode(' ',$errors));}else{$pdo->prepare('INSERT INTO admin_audit_log(admin_id,action,entity_type,entity_id,detail) VALUES(?,?,?,?,?)')->execute([$_SESSION['admin_id'],'customize','website','settings','Updated homepage and inventory settings']);$pdo->commit();flash('success','Store settings saved.');}
 header('Location: customize.php');exit;
}
$vals=[];foreach($defaults as $k=>$v)$vals[$k]=setting($k,$v);
?>
<div class="admin-title"><p>STORE DESIGN</p><h2>Website customisation</h2></div><section class="admin-panel"><p>These settings update supported homepage content and the inventory alert threshold.</p><form method="post" class="settings-form"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><label>Promotional message<input name="promo_message" maxlength="200" value="<?=e($vals['promo_message'])?>"></label><label>Hero heading<input name="hero_title" maxlength="180" value="<?=e($vals['hero_title'])?>"></label><label>Hero description<textarea name="hero_subtitle" maxlength="400"><?=e($vals['hero_subtitle'])?></textarea></label><label>Hero image URL (optional)<input name="hero_image" maxlength="500" value="<?=e($vals['hero_image'])?>"></label><label>Primary colour<input type="color" name="theme_primary" value="<?=e($vals['theme_primary'])?>"></label><label>Low stock threshold<input type="number" min="1" max="100" name="low_stock_threshold" value="<?=e($vals['low_stock_threshold'])?>"></label><button class="save">Save settings</button></form></section><?php require 'footer.php';
