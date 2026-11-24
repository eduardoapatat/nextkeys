<?php

namespace App\Enums;

/**
 * The 16 regions of Chile by their ISO 3166-2:CL code, north to south, with
 * their communes (docs/domain/shipping.md). Not the catalog's `Region`,
 * which is the region a game key is sold for.
 */
enum ChileRegion: string
{
    case AricaYParinacota = 'AP';
    case Tarapaca = 'TA';
    case Antofagasta = 'AN';
    case Atacama = 'AT';
    case Coquimbo = 'CO';
    case Valparaiso = 'VS';
    case Metropolitana = 'RM';
    case OHiggins = 'LI';
    case Maule = 'ML';
    case Nuble = 'NB';
    case Biobio = 'BI';
    case Araucania = 'AR';
    case LosRios = 'LR';
    case LosLagos = 'LL';
    case Aysen = 'AI';
    case Magallanes = 'MA';

    public function label(): string
    {
        return match ($this) {
            self::AricaYParinacota => 'Arica y Parinacota',
            self::Tarapaca => 'Tarapacá',
            self::Antofagasta => 'Antofagasta',
            self::Atacama => 'Atacama',
            self::Coquimbo => 'Coquimbo',
            self::Valparaiso => 'Valparaíso',
            self::Metropolitana => 'Metropolitana de Santiago',
            self::OHiggins => 'Libertador General Bernardo O\'Higgins',
            self::Maule => 'Maule',
            self::Nuble => 'Ñuble',
            self::Biobio => 'Biobío',
            self::Araucania => 'La Araucanía',
            self::LosRios => 'Los Ríos',
            self::LosLagos => 'Los Lagos',
            self::Aysen => 'Aysén del General Carlos Ibáñez del Campo',
            self::Magallanes => 'Magallanes y de la Antártica Chilena',
        };
    }

    /**
     * The region's communes, by province, as their names are written.
     *
     * @return list<string>
     */
    public function communes(): array
    {
        return match ($this) {
            self::AricaYParinacota => ['Arica', 'Camarones', 'Putre', 'General Lagos'],
            self::Tarapaca => ['Iquique', 'Alto Hospicio', 'Pozo Almonte', 'Camiña', 'Colchane', 'Huara', 'Pica'],
            self::Antofagasta => ['Antofagasta', 'Mejillones', 'Sierra Gorda', 'Taltal', 'Calama', 'Ollagüe', 'San Pedro de Atacama', 'Tocopilla', 'María Elena'],
            self::Atacama => ['Copiapó', 'Caldera', 'Tierra Amarilla', 'Chañaral', 'Diego de Almagro', 'Vallenar', 'Alto del Carmen', 'Freirina', 'Huasco'],
            self::Coquimbo => ['La Serena', 'Coquimbo', 'Andacollo', 'La Higuera', 'Paihuano', 'Vicuña', 'Illapel', 'Canela', 'Los Vilos', 'Salamanca', 'Ovalle', 'Combarbalá', 'Monte Patria', 'Punitaqui', 'Río Hurtado'],
            self::Valparaiso => [
                'Valparaíso', 'Casablanca', 'Concón', 'Juan Fernández', 'Puchuncaví', 'Quintero', 'Viña del Mar',
                'Isla de Pascua',
                'Los Andes', 'Calle Larga', 'Rinconada', 'San Esteban',
                'La Ligua', 'Cabildo', 'Papudo', 'Petorca', 'Zapallar',
                'Quillota', 'La Calera', 'Hijuelas', 'La Cruz', 'Nogales',
                'San Antonio', 'Algarrobo', 'Cartagena', 'El Quisco', 'El Tabo', 'Santo Domingo',
                'San Felipe', 'Catemu', 'Llaillay', 'Panquehue', 'Putaendo', 'Santa María',
                'Quilpué', 'Limache', 'Olmué', 'Villa Alemana',
            ],
            self::Metropolitana => [
                'Santiago', 'Cerrillos', 'Cerro Navia', 'Conchalí', 'El Bosque', 'Estación Central', 'Huechuraba', 'Independencia',
                'La Cisterna', 'La Florida', 'La Granja', 'La Pintana', 'La Reina', 'Las Condes', 'Lo Barnechea', 'Lo Espejo',
                'Lo Prado', 'Macul', 'Maipú', 'Ñuñoa', 'Pedro Aguirre Cerda', 'Peñalolén', 'Providencia', 'Pudahuel',
                'Quilicura', 'Quinta Normal', 'Recoleta', 'Renca', 'San Joaquín', 'San Miguel', 'San Ramón', 'Vitacura',
                'Puente Alto', 'Pirque', 'San José de Maipo',
                'Colina', 'Lampa', 'Tiltil',
                'San Bernardo', 'Buin', 'Calera de Tango', 'Paine',
                'Melipilla', 'Alhué', 'Curacaví', 'María Pinto', 'San Pedro',
                'Talagante', 'El Monte', 'Isla de Maipo', 'Padre Hurtado', 'Peñaflor',
            ],
            self::OHiggins => [
                'Rancagua', 'Codegua', 'Coinco', 'Coltauco', 'Doñihue', 'Graneros', 'Las Cabras', 'Machalí', 'Malloa',
                'Mostazal', 'Olivar', 'Peumo', 'Pichidegua', 'Quinta de Tilcoco', 'Rengo', 'Requínoa', 'San Vicente',
                'Pichilemu', 'La Estrella', 'Litueche', 'Marchigüe', 'Navidad', 'Paredones',
                'San Fernando', 'Chépica', 'Chimbarongo', 'Lolol', 'Nancagua', 'Palmilla', 'Peralillo', 'Placilla', 'Pumanque', 'Santa Cruz',
            ],
            self::Maule => [
                'Talca', 'Constitución', 'Curepto', 'Empedrado', 'Maule', 'Pelarco', 'Pencahue', 'Río Claro', 'San Clemente', 'San Rafael',
                'Cauquenes', 'Chanco', 'Pelluhue',
                'Curicó', 'Hualañé', 'Licantén', 'Molina', 'Rauco', 'Romeral', 'Sagrada Familia', 'Teno', 'Vichuquén',
                'Linares', 'Colbún', 'Longaví', 'Parral', 'Retiro', 'San Javier', 'Villa Alegre', 'Yerbas Buenas',
            ],
            self::Nuble => [
                'Chillán', 'Bulnes', 'Chillán Viejo', 'El Carmen', 'Pemuco', 'Pinto', 'Quillón', 'San Ignacio', 'Yungay',
                'Quirihue', 'Cobquecura', 'Coelemu', 'Ninhue', 'Portezuelo', 'Ránquil', 'Treguaco',
                'San Carlos', 'Coihueco', 'Ñiquén', 'San Fabián', 'San Nicolás',
            ],
            self::Biobio => [
                'Concepción', 'Coronel', 'Chiguayante', 'Florida', 'Hualqui', 'Lota', 'Penco', 'San Pedro de la Paz', 'Santa Juana', 'Talcahuano', 'Tomé', 'Hualpén',
                'Lebu', 'Arauco', 'Cañete', 'Contulmo', 'Curanilahue', 'Los Álamos', 'Tirúa',
                'Los Ángeles', 'Antuco', 'Cabrero', 'Laja', 'Mulchén', 'Nacimiento', 'Negrete', 'Quilaco', 'Quilleco', 'San Rosendo', 'Santa Bárbara', 'Tucapel', 'Yumbel', 'Alto Biobío',
            ],
            self::Araucania => [
                'Temuco', 'Carahue', 'Cunco', 'Curarrehue', 'Freire', 'Galvarino', 'Gorbea', 'Lautaro', 'Loncoche', 'Melipeuco', 'Nueva Imperial',
                'Padre Las Casas', 'Perquenco', 'Pitrufquén', 'Pucón', 'Saavedra', 'Teodoro Schmidt', 'Toltén', 'Vilcún', 'Villarrica', 'Cholchol',
                'Angol', 'Collipulli', 'Curacautín', 'Ercilla', 'Lonquimay', 'Los Sauces', 'Lumaco', 'Purén', 'Renaico', 'Traiguén', 'Victoria',
            ],
            self::LosRios => [
                'Valdivia', 'Corral', 'Lanco', 'Los Lagos', 'Máfil', 'Mariquina', 'Paillaco', 'Panguipulli',
                'La Unión', 'Futrono', 'Lago Ranco', 'Río Bueno',
            ],
            self::LosLagos => [
                'Puerto Montt', 'Calbuco', 'Cochamó', 'Fresia', 'Frutillar', 'Los Muermos', 'Llanquihue', 'Maullín', 'Puerto Varas',
                'Castro', 'Ancud', 'Chonchi', 'Curaco de Vélez', 'Dalcahue', 'Puqueldón', 'Queilén', 'Quellón', 'Quemchi', 'Quinchao',
                'Osorno', 'Puerto Octay', 'Purranque', 'Puyehue', 'Río Negro', 'San Juan de la Costa', 'San Pablo',
                'Chaitén', 'Futaleufú', 'Hualaihué', 'Palena',
            ],
            self::Aysen => ['Coyhaique', 'Lago Verde', 'Aysén', 'Cisnes', 'Guaitecas', 'Cochrane', 'O\'Higgins', 'Tortel', 'Chile Chico', 'Río Ibáñez'],
            self::Magallanes => ['Punta Arenas', 'Laguna Blanca', 'Río Verde', 'San Gregorio', 'Cabo de Hornos', 'Antártica', 'Porvenir', 'Primavera', 'Timaukel', 'Natales', 'Torres del Paine'],
        };
    }

    public function hasCommune(string $commune): bool
    {
        return in_array($commune, $this->communes(), true);
    }
}
