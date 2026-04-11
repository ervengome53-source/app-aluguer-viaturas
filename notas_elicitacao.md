#Sistema de Aluguer de Viaturas 
Disciplina: Engenharia de Software II

#1- Problemas Identificados
Após entrevista simulada e brainstorming, foram identificados os seguintes problemas:

      Dificuldade em saber quais viaturas estão disponíveis em tempo real
      Uso de registos manuais caderno
      Erros no cálculo do valor do aluguer
      Risco de dupla reserva da mesma viatura
      Falta de controlo organizado do histórico de aluguer
      Demora no atendimento ao cliente


#2- Descobertas da Entrevista

Stakeholder entrevistado: Funcionário da empresa de aluguer 
Principais respostas obtidas:

    O controlo atual é feito manualmente.
    Não existe verificação automática de conflitos de datas.
    O cálculo do valor é feito manualmente com base nos dias.
    Há dificuldades quando clientes devolvem viaturas com atraso.
    O funcionário precisa de uma forma rápida de consultar disponibilidade.

Necessidades identificadas:

    Sistema que mostre viaturas disponíveis em tempo real
    Cálculo automático do valor do aluguer
    Bloqueio automático de reservas sobrepostas
    Atualização automática do estado da viatura


#3- Cenários do Dia-a-Dia   

Cenário 1 – Cliente precisa de viatura urgente

    Situação: Cliente chega e precisa alugar imediatamente.
    
    Problema: Demora para verificar disponibilidade manualmente.
    
    Necessidade: Sistema deve mostrar viaturas disponíveis instantaneamente.

Cenário 2 – Devolução com atraso

    Situação: Cliente devolve viatura após a data prevista.
    
    Problema: Funcionário calcula multa manualmente.
    
    Necessidade: Sistema deve calcular automaticamente multa por atraso.

Cenário 3 – Dupla reserva
    
    Situação: Dois funcionários registam reserva da mesma viatura no mesmo período.
    
    Problema: Conflito de reservas e insatisfação do cliente.
    
    Necessidade: Sistema deve impedir reservas sobrepostas.


#4- Restrições Identificadas

    Uma viatura não pode estar em dois alugueres simultaneamente.
    
    Apenas utilizadores autenticados podem aceder ao sistema.
    
    Apenas administrador pode remover utilizadores.
    
    Reservas só podem ser feitas para viaturas disponíveis.
    
    O sistema deve guardar histórico de aluguer para consulta futura.
