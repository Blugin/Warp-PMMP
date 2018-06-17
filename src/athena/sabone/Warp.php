<?php
namespace athena\sabone;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\event\player\{PlayerInteractEvent,PlayerBreakEvent};
use pocketmine\command\{Command,CommandSender};

class Warp extends PluginBase implements Listener{
	public $g=[];
	public $config;
	public $commands=[];
	public $portals=[];
	public $making=[];
	public $u="[워프] ";
	/*
	$name:{
 		portals:{
			(Position) (Position) ...
		}
		destination:(Position)
		banned:(bool)
	}
	*/
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config=new Config($this->getDataFolder()."\config.json",Config::JSON);
		$this->g=$this->config->getAll();
		$this->commands=array_keys($this->g);
		foreach($this->commands as $m){
			$this->getServer()->getCommandMap()->register($m,new Command($m));
		}
		foreach($this->g as $name=>$value){
			array_push($this->portals,$value["portals"]);
		}
		
		//포탈에 예쁘게 파티클 꾸미기 ☆☆
	}
	public function onDisable(){
		$this->save();
	}
	public function onTouch(PlayerInteractEvent $ev){
		$player=$ev->getPlayer();
		if(in_array($player,$this->making){
			/* $block=$ev->getBlock();
			$pos=Position::fromObject($block,$block->getLevel()).add(0,1,0); */
			$pos=Position::fromObject($ev->getTouchVector(),$player->getLevel()).add(0,1,0);
			$this->addDes($this->making[$player],$pos);
			unset($this->making[$player]);
			$player->sendMessage("§e{$u}포탈이 생성되었습니다!");
			return;
		}
	}
	public function onBreak(PlayerBreakEvent $ev){
		$player=$ev->getPlayer();
		if(in_array($player,$this->making){
			$block=$ev->getBlock();
			$pos=Position::fromObject($block,$block->getLevel()).add(0,1,0);
			$this->addPortal($this->making[$player],$pos);
			$player->sendMessage("§e{$u}포탈을 더 추가하려면 더 부수고 그만 만드려면 도착지 아래 블럭을 터치해주세요.");
			return;
		}
	}
	public function onCommand(CommandSender $sender,Command $cmd,string $label,array $args):bool{
		if(!($sender instanceof Player)){
			return false;
		}
		if(in_array(strtolower($label),$this->commands)){
			if($this->isBanned($label)) return false;
			$this->warp($sender,$label);
			return true;
		}
		if($label==="워프"||$label==="warp"){
			if(count($args)<2) return false;
			if($args[0]==="추가"||$args[0]==="a"){
				//워프 추가 $name $amount
				if(count($args)<3){
					$sender->sendTip("§e{$u}/워프 추가 $name $amount");
					return false;
				}
				if(in_array($sender,$this->making)){
					$sender->sendMessage("§e{$u}이미 만드는중입니다.");
					return false;
				}
				if(!is_numberic($args[2])){
					$sender->sendTip("§e{$u}포탈수를 숫자로 입력해주세요.");
					return false;
				}
				if($this->isWarp($args[1])){
					$sender->sendTip("§e{$u}이미 존재하는 워프입니다.");
					return false;
				}
				array_push($this->making,array($sender=>$args[1]));
				$sender->sendMessage("§e{$u}포탈을 설치할곳의 아래 블럭을 부서주세요.");
				$this->getServer()->getCommandMap()->register($args[1],new Command($args[1]));
				$this->addWarp($args[1]);
				return true;
			}
			if($args[0]==="삭제"||$args[0]==="d"){
				//워프 삭제 $name
				if(count($args)<2){
					$sender->sendTip("§e{$u}/워프 삭제 $name");
					return false;
				}
				if(!$this->isWarp($args[1])){
					$sender->sendTip("§e{$u}존재하지 않는 워프입니다.");
					return false;
				}
				$this->delWarp($args[1]);
				$sender->sendMessage("§e{$u}워프를 삭제했습니다.");
				$this->getServer()->getCommandMap()->runregister(new Command($args[1]));
				return true;
			}
			if($args[0]==="금지"||$args[0]==="b"){
				//워프 금지 $name
				if(count($args<2)){
					$sender->sendMessage("§e{$u}/워프 금지 $name");
					return false;
				}
				if(!$this->isWarp($args[1])){
					$sender->sendMessage("§e{$u}존재하지 않는 워프입니다.");
					return false;
				}
				if($this->isBanned($args[1])){
					$this->setBanned($args[1],false);
					$sender->sendMessage("§e{$u}워프의 금지를 풀었습니다.");
					return true;
				}
				$this->setBanned($args[1],true);
				$sender->sendMessage("§e{$u}워프를 금지했습니다.");
				return true;
			}
			if($args[0]==="목록"||$args[0]==="l"){
				//워프 목록
				$n="";
				foreach($this->commands as $q){
					$n+=$q.", ";
				}
				$sender->sendMessage("§e{$u}워프의 개수 : {count($g)}\n {$n}");
				return true;
			}
		}
	}
	public function onSnick(PlayerToggleSnickEvent $ev){
		$player=$ev->getPlayer();
		$position=$player->getPostition();
		if($this->isWarp($position)) return;
		if($this->isBanned($position)) return;
		$this->warp($player,$position);
		return;
	}
	public function addWarp($name){
		if($name instanceof Position){
			$name=$this->getWarpName($name);
		}
		$this->g[strtolower($name)]=array("portals"=>array(),"destination"=>null,"banned"=>false);
	}
	public function addPortal($name,array $portals){
		if($name instanceof Position){
			$name=$this->getWarpName($name);
		}
		$p=$this->g[strtolower($name)]["portals"];
		foreach($portals as $a){
			array_push($p,$this->a($a));
		}
		$this->g[strtolower($name)]["portals"]=$p;
	}
	public function delPortal($name,Position $portal){
		if($name instanceof Position){
			$name=$this->getWarpName($name);
		}
		array_splice($g[strtolower($name)]["portals"],array_search($this->a($portal),$g[strtolower($name)]["portals"]),1);
	}
	public function setDes($name,Position $pos){
		if($name instanceof Position){
			$name=$this->getWarpName($name);
		}
		$this->g[strtolower($name)]["destination"]=$this->a($pos);
	}
	public function isBanned($name){
		if($name instanceof Position){
			$name=$this->getWarpName($name);
		}
		return $$this->g[strtolower($name)];
	}
	public function setBanned($name,bool $banned=true){
		if($name instanceof Position){
			$name=$this->getWarpName($name);
		}
		$this->g[strtolower($name)]["banned"]=$banned;
	}
	public function delWarp($name){
		if($name instanceof Position){
			$name=$this->getWarpName($name);
		}
		unset($this->g[strtolower($name)]);
	}
	public function isWarp($name){
		if($name instanceof Position){
			return in_array($this->a($pos),$this->portals);
		}
		return in_array($name,$this->g);
	}
	public function getWarpName(Position $pos){
		foreach($this->g as $name=>$value){
			if(in_array($this->a($pos),$value["portals"])){
				return $name;
			}
		}
	}
	public function getDes($name){
		if($name instanceof Position){
			$name=$this->getWarpName($name);
		}
		return $this->g[$name]["destination"];
	}
	public function a(Position $pos){//약분?
		$pos->x=(int)$pos->x;
		$pos->y=(int)$pos->y;
		$pos->z=(int)$pos->z;
		return $pos;
	}
	function warp($player,$name){
		if($name instanceof Position){
			$name=$this->getWarpName($name);
		}
		$des=$this->getDes($name);
		$player->teleport($des);
		$player->sendTip("§6워프 !");
		//돈 줄이기
	}
	function save(){
		$this->config->setAll($this->g);
		$this->config->save();
	}
}