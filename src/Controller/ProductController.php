<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\CheckoutType;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Tests\Fixtures\ToString;

/**
 * @Route("/product")
 */
class ProductController extends AbstractController
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/", name="product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository): Response
    {
//        $this->session->clear();
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="product_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="product_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('product_index');
    }

    /**
     * @Route("/{id}/cart", name="addToCart")
     */
    public function addToCart(Product $product)
    {
        $id = $product->getId();

        $getCart = $this->session->get('cart');
        if(isset($getCart[$id])){
            $getCart[$id]['aantal']++;
        }else{
            $getCart[$id] = array('aantal' => 1);
        }
        $this->session->set('cart', $getCart);
        $cart = $this->session->get('cart');
        $cartArray = [];
        $totaal = 0;

        foreach($cart as $id => $product) {
            $res = $this->getDoctrine()
                ->getRepository(Product::class)
                ->find($id);

            array_push($cartArray, [$id, $product['aantal'], $res]);

            $totaal = $totaal + ($product['aantal'] * $res->getPrijs());
        }

//        echo '<pre>' . print_r($_SESSION, TRUE) . '</pre>';

        return $this->render('product/addToCart.html.twig', [
            'totaal' => $totaal,
            'cart' => $cartArray,
        ]);
    }

    /**
     * @Route("/checkout", name="checkout", methods={"GET","POST"})
     */
    public function checkout(Request $request, \Swift_Mailer $mailer)
    {
        $cart = $this->session->get('cart');
        $cartArray = [];
        $totaal = 0;

        foreach ($cart as $id => $product) {
            $res = $this->getDoctrine()
                ->getRepository(Product::class)
                ->find($id);

            array_push($cartArray, [$id, $product['aantal'], $res]);

            $totaal = $totaal + ($product['aantal'] * $res->getPrijs());
        }

        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $naam = $form->get('Naam')->getData();
            $email = $form->get('Email')->getData();

            $message = (new \Swift_Message('Factuur'))
                ->setFrom('christiaangerritsen2000@gmail.com')
                ->setTo($email)
                ->setBody(
                    '<p>Dit is de factuur</p>'
                );

                $mailer->send($message);

                $this->session->clear();
                return $this->redirect('/');
        }

            return $this->render('product/Checkout.html.twig', [
                'form' => $form->createView(),
                'totaal' => $totaal,
                'cart' => $cartArray
            ]);
    }
}
