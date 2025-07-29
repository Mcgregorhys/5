<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $lp = null;

     #[ORM\Column(length: 255)]
    private ?string $kod = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageFilename = null;
    


    #[ORM\Column(length: 255)]
    private ?string $nazwaProduktu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amount = null;
    
    #[ORM\Column(length: 255)]
    private ?string $cena_netto = null;

    #[ORM\Column(length: 255)]
    private ?string $vat = null;

    #[ORM\Column(length: 255)]
    private ?string $cena_brutto = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(length: 255)]
    private ?string $netto_minus30 = null;

    #[ORM\Column(length: 255)]
    private ?string $netto_minus20 = null;

    #[ORM\Column(length: 255)]
    private ?string $eur_minus20 = null;

    #[ORM\Column(length: 255)]
    private ?string $eur_minus30 = null;

   

    

    

   

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLp(): ?string
    {
        return $this->lp;
    }

    public function setLp(string $lp): static
    {
        $this->lp = $lp;

        return $this;
    }

    public function getKod(): ?string
    {
        return $this->kod;
    }

    public function setKod(string $kod): static
    {
        $this->kod = $kod;

        return $this;
    }

    public function getNazwaProduktu(): ?string
    {
        return $this->nazwaProduktu;
    }

    public function setNazwaProduktu(string $nazwaProduktu): static
    {
        $this->nazwaProduktu = $nazwaProduktu;

        return $this;
    }
    public function getCenaNetto(): ?string
    {
        return $this->cena_netto;
    }

    public function setCenaNetto(string $cena_netto): static
    {
        $this->cena_netto = $cena_netto;

        return $this;
    }

    public function getVat(): ?string
    {
        return $this->vat;
    }

    public function setVat(string $vat): static
    {
        $this->vat = $vat;

        return $this;
    }

    public function getCenaBrutto(): ?string
    {
        return $this->cena_brutto;
    }

    public function setCenaBrutto(string $cena_brutto): static
    {
        $this->cena_brutto = $cena_brutto;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }
    public function getImageFilename(): ?string
    {
    return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): self
    {
    $this->imageFilename = $imageFilename;
    return $this;
    }

    public function getNettoMinus30(): ?string
    {
        return $this->netto_minus30;
    }

    public function setNettoMinus30(string $netto_minus30): static
    {
        $this->netto_minus30 = $netto_minus30;

        return $this;
    }

    public function getNettoMinus20(): ?string
    {
        return $this->netto_minus20;
    }

    public function setNettoMinus20(string $netto_minus20): static
    {
        $this->netto_minus20 = $netto_minus20;

        return $this;
    }

    public function getEurMinus20(): ?string
    {
        return $this->eur_minus20;
    }

    public function setEurMinus20(string $eur_minus20): static
    {
        $this->eur_minus20 = $eur_minus20;

        return $this;
    }

    public function getEurMinus30(): ?string
    {
        return $this->eur_minus30;
    }

    public function setEurMinus30(string $eur_minus30): static
    {
        $this->eur_minus30 = $eur_minus30;

        return $this;
    }

    

    
   
}
