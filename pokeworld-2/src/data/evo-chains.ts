// Full Gen-1 evolution chain data.
// Each EvoStage has: id of the Pokémon, and optionally how it evolves INTO the next stage.

export type EvoTrigger =
  | { type: 'level'; level: number }
  | { type: 'stone'; stone: string }
  | { type: 'trade' }

export interface EvoStage {
  id: number
  next?: EvoTrigger
}

export interface EeveeBranch {
  id: number
  stone: string
}

// ── Linear chains ─────────────────────────────────────────────────────
// Each array is one complete chain, ordered base → final
export const EVO_CHAINS: EvoStage[][] = [

  // ── 3-stage ──────────────────────────────────────────────────────
  [{ id:1,   next:{type:'level',level:16}                   }, { id:2,   next:{type:'level',level:32}                   }, { id:3   }],  // Bulbasaur
  [{ id:4,   next:{type:'level',level:16}                   }, { id:5,   next:{type:'level',level:36}                   }, { id:6   }],  // Charmander
  [{ id:7,   next:{type:'level',level:16}                   }, { id:8,   next:{type:'level',level:36}                   }, { id:9   }],  // Squirtle
  [{ id:10,  next:{type:'level',level:7}                    }, { id:11,  next:{type:'level',level:10}                   }, { id:12  }],  // Caterpie
  [{ id:13,  next:{type:'level',level:7}                    }, { id:14,  next:{type:'level',level:10}                   }, { id:15  }],  // Weedle
  [{ id:16,  next:{type:'level',level:18}                   }, { id:17,  next:{type:'level',level:36}                   }, { id:18  }],  // Pidgey
  [{ id:29,  next:{type:'level',level:16}                   }, { id:30,  next:{type:'stone',stone:'Moon Stone'}         }, { id:31  }],  // Nidoran♀
  [{ id:32,  next:{type:'level',level:16}                   }, { id:33,  next:{type:'stone',stone:'Moon Stone'}         }, { id:34  }],  // Nidoran♂
  [{ id:43,  next:{type:'level',level:21}                   }, { id:44,  next:{type:'stone',stone:'Leaf Stone'}         }, { id:45  }],  // Oddish
  [{ id:60,  next:{type:'level',level:25}                   }, { id:61,  next:{type:'stone',stone:'Water Stone'}        }, { id:62  }],  // Poliwag
  [{ id:63,  next:{type:'level',level:16}                   }, { id:64,  next:{type:'trade'}                            }, { id:65  }],  // Abra
  [{ id:66,  next:{type:'level',level:28}                   }, { id:67,  next:{type:'trade'}                            }, { id:68  }],  // Machop
  [{ id:69,  next:{type:'level',level:21}                   }, { id:70,  next:{type:'stone',stone:'Leaf Stone'}         }, { id:71  }],  // Bellsprout
  [{ id:74,  next:{type:'level',level:25}                   }, { id:75,  next:{type:'trade'}                            }, { id:76  }],  // Geodude
  [{ id:92,  next:{type:'level',level:25}                   }, { id:93,  next:{type:'trade'}                            }, { id:94  }],  // Gastly
  [{ id:147, next:{type:'level',level:30}                   }, { id:148, next:{type:'level',level:55}                   }, { id:149 }],  // Dratini

  // ── 2-stage ──────────────────────────────────────────────────────
  [{ id:19,  next:{type:'level',level:20}                   }, { id:20  }],  // Rattata
  [{ id:21,  next:{type:'level',level:20}                   }, { id:22  }],  // Spearow
  [{ id:23,  next:{type:'level',level:22}                   }, { id:24  }],  // Ekans
  [{ id:25,  next:{type:'stone',stone:'Thunder Stone'}      }, { id:26  }],  // Pikachu
  [{ id:27,  next:{type:'level',level:22}                   }, { id:28  }],  // Sandshrew
  [{ id:35,  next:{type:'stone',stone:'Moon Stone'}         }, { id:36  }],  // Clefairy
  [{ id:37,  next:{type:'stone',stone:'Fire Stone'}         }, { id:38  }],  // Vulpix
  [{ id:39,  next:{type:'stone',stone:'Moon Stone'}         }, { id:40  }],  // Jigglypuff
  [{ id:41,  next:{type:'level',level:22}                   }, { id:42  }],  // Zubat
  [{ id:46,  next:{type:'level',level:24}                   }, { id:47  }],  // Paras
  [{ id:48,  next:{type:'level',level:31}                   }, { id:49  }],  // Venonat
  [{ id:50,  next:{type:'level',level:26}                   }, { id:51  }],  // Diglett
  [{ id:52,  next:{type:'level',level:28}                   }, { id:53  }],  // Meowth
  [{ id:54,  next:{type:'level',level:33}                   }, { id:55  }],  // Psyduck
  [{ id:56,  next:{type:'level',level:28}                   }, { id:57  }],  // Mankey
  [{ id:58,  next:{type:'stone',stone:'Fire Stone'}         }, { id:59  }],  // Growlithe
  [{ id:72,  next:{type:'level',level:30}                   }, { id:73  }],  // Tentacool
  [{ id:77,  next:{type:'level',level:40}                   }, { id:78  }],  // Ponyta
  [{ id:79,  next:{type:'level',level:37}                   }, { id:80  }],  // Slowpoke
  [{ id:81,  next:{type:'level',level:30}                   }, { id:82  }],  // Magnemite
  [{ id:84,  next:{type:'level',level:31}                   }, { id:85  }],  // Doduo
  [{ id:86,  next:{type:'level',level:34}                   }, { id:87  }],  // Seel
  [{ id:88,  next:{type:'level',level:38}                   }, { id:89  }],  // Grimer
  [{ id:90,  next:{type:'stone',stone:'Water Stone'}        }, { id:91  }],  // Shellder
  [{ id:96,  next:{type:'level',level:26}                   }, { id:97  }],  // Drowzee
  [{ id:98,  next:{type:'level',level:28}                   }, { id:99  }],  // Krabby
  [{ id:100, next:{type:'level',level:30}                   }, { id:101 }],  // Voltorb
  [{ id:102, next:{type:'stone',stone:'Leaf Stone'}         }, { id:103 }],  // Exeggcute
  [{ id:104, next:{type:'level',level:28}                   }, { id:105 }],  // Cubone
  [{ id:109, next:{type:'level',level:35}                   }, { id:110 }],  // Koffing
  [{ id:111, next:{type:'level',level:42}                   }, { id:112 }],  // Rhyhorn
  [{ id:116, next:{type:'level',level:32}                   }, { id:117 }],  // Horsea
  [{ id:118, next:{type:'level',level:33}                   }, { id:119 }],  // Goldeen
  [{ id:120, next:{type:'stone',stone:'Water Stone'}        }, { id:121 }],  // Staryu
  [{ id:129, next:{type:'level',level:20}                   }, { id:130 }],  // Magikarp
  [{ id:138, next:{type:'level',level:40}                   }, { id:139 }],  // Omanyte
  [{ id:140, next:{type:'level',level:40}                   }, { id:141 }],  // Kabuto
]

// ── Eevee branching evolution ──────────────────────────────────────────
export const EEVEE_BASE_ID = 133
export const EEVEE_BRANCH_IDS = [134, 135, 136]
export const EEVEE_BRANCHES: EeveeBranch[] = [
  { id: 134, stone: 'Water Stone'   },  // Vaporeon
  { id: 135, stone: 'Thunder Stone' },  // Jolteon
  { id: 136, stone: 'Fire Stone'    },  // Flareon
]

// ── Lookup helpers ─────────────────────────────────────────────────────
/** Returns the linear chain that contains this Pokémon, or null. */
export function findChain(id: number): EvoStage[] | null {
  return EVO_CHAINS.find(chain => chain.some(stage => stage.id === id)) ?? null
}

/** True if this Pokémon is Eevee or one of its Gen-1 evolutions. */
export function isEeveeLine(id: number): boolean {
  return id === EEVEE_BASE_ID || EEVEE_BRANCH_IDS.includes(id)
}
