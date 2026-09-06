# Especificação do Motor PVZ

## Requisitos de Tela
- **Resolução**: 900x600 (Fixa, escalável por CSS).
- **Camadas**: Background -> Grid Shadow -> Entities (Z-Sorted) -> Projectiles -> UI -> Cursor.

## Regras de Plantas
- **Cooldown**: Progress bar visual na carta.
- **Detecção de Colisão**: Apenas 1 planta por tile (Exceto em caso de Fusion).
- **HP**: Girassol (100), Disparervilha (100), Noz-Barreira (4000).

## Regras de Zumbis
- **Dano**: 100 de dano por segundo de mastigação.
- **Velocidade**: 0.38 unidades/frame (Aproximadamente 4s para cruzar um tile).

## Fusion (RH Mod)
- Se Planta A (Tile) == Girassol E Planta Segurada == Disparervilha -> Spawn SunPea.
