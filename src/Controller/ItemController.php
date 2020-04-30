<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\EditProductType;
use App\Form\ProductType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function listItems(Request $request)
    {
        $item = $this->getAllItems();
        $form = $this->createForm(ProductType::class, $item);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->addItem($form->getData());
            return $this->redirect("/");
        }

        return $this->render('item/index.html.twig', [
            "items" => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{id}")
     */
    public function editItem(Request $request, $id)
    {
        $item = $this->getItemByID($id);
        $form = $this->createForm(EditProductType::class);
        $form->get('amount')->setData($item->getAmount());
        $form->get('name')->setData($item->getName());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->get('submit')->isClicked()) {
            $this->saveItem($form->getData(), $id);
            return $this->redirect("/");
        } elseif ($form->isSubmitted() && $form->get('cancel')->isClicked()) {
            return $this->redirect("/");
        }
        return $this->render('item/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}")
     */
    public function deleteItem(Request $request, $id)
    {
        if ($id <= 0) {
            throw $this->createNotFoundException('ID ' . $id . ' is not valid');
        };
        $conn = $this->getConnection();
        $sql = "DELETE FROM map_item WHERE ID = :id";
        $st = $conn->prepare($sql);
        $st->bindParam('id', $id, \PDO::PARAM_INT);
		$st->execute();
        return $this->redirect('/');
    }

    private function getAllItems()
    {
        $items = [];
        $conn = $this->getConnection();
        $sql = "SELECT * FROM map_item";
        // $result = $conn->query($sql, \PDO::FETCH_ASSOC);
        foreach ($conn->query($sql) as $row) {
            $item = new Item();
            $item->setId($row["ID"]);
            $item->setName($row["name"]);
            $item->setAmount($row["amount"]);
            $items[] = $item;
        }

        return $items;
    }

    private function getItemByID(int $id)
    {
        if ($id <= 0) {
            throw $this->createNotFoundException('ID ' . $id . ' is not valid');
        };

        $conn = $this->getConnection();
		$item = null;
		
        if ($st = $conn->prepare("SELECT * FROM map_item WHERE ID=:id")) {
            $st->bindParam("id", $id, \PDO::PARAM_INT);
			$st->execute();
			$row = $st->fetch(\PDO::FETCH_ASSOC);
            $item = new Item();
            $item->setId($row['ID']);
            $item->setName($row['name']);
            $item->setAmount($row['amount']);
        }
        return $item;
    }

    public function addItem($data)
    {
        $conn = $this->getConnection();
        $st = $conn->prepare("INSERT INTO map_item (amount, name) VALUES (:amount, :name)");
        $st->bindParam('amount', $data["amount"]);
        $st->bindParam('name', $data["name"]);
		$st->execute();
    }

    public function saveItem($data, $id)
    {
        $conn = $this->getConnection();
        $st = $conn->prepare("UPDATE map_item SET Name=:name, Amount=:amount WHERE ID=:id");
        $st->bindParam('name', $data["name"]);
        $st->bindParam('amount', $data["amount"]);
		$st->bindParam('id', $id, \PDO::PARAM_INT);
		$st->execute();
    }
    private function getConnection(): \PDO
    {
        return new \PDO('mysql:host=' . $_ENV['DB_SERVER'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ]);
    }
}
